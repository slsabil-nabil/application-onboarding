<?php

namespace Slsabil\ApplicationOnboarding\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Slsabil\ApplicationOnboarding\Models\BusinessApplication;
use Slsabil\ApplicationOnboarding\Models\FormField;
use Slsabil\NotificationCenter\Models\Notification;
use Slsabil\NotificationCenter\Models\NotificationRecipient;

class PublicApplicationController extends Controller
{
    /**
     * عرض نموذج التقديم (مع دعم رابط إعادة التعبئة عبر التوكن)
     */
    public function create(?string $token = null)
    {
        $application = null;

        if ($token) {
            $application = BusinessApplication::where('resubmit_token', $token)->firstOrFail();

            if ($application->resubmit_expires_at && Carbon::now()->gt($application->resubmit_expires_at)) {
                abort(410, 'Resubmission link expired.');
            }
        }

        // الحقول الديناميكية من جدول form_fields
        $fields = FormField::orderBy('order')->get();
        $hasForm = $fields->isNotEmpty();

        return view('application-onboarding::public.apply', [
            'application' => $application,
            'fields' => $fields,
            'hasForm' => $hasForm,
        ]);
    }

    /**
     * إرسال نموذج التقديم (إنشاء جديد أو تحديث نفس السجل عند وجود توكن)
     */
    public function submit(Request $request, ?string $token = null)
    {
        // فحص الحقول الأساسية فقط، والباقي يخزن في form_data
        $data = $request->validate([
            'business_name' => 'required|string|max:255',
            'industry_type' => 'nullable|string|max:255',
            'industry_type_other' => 'nullable|string|max:255',
            'owner_name' => 'required|string|max:255',
            'owner_email' => 'required|email|max:255',
            'owner_phone' => 'nullable|string|max:40',
            // ملاحظة: حقول الملفات لا نجعلها required هنا حتى تبقى مرنة
        ]);

        // معالجة خيار "Other" لنوع الصناعة
        $industry = $data['industry_type'] ?? null;
        if (
            ($industry === 'Other' || $industry === 'أخرى' || $industry === 'اخرى')
            && !empty($data['industry_type_other'])
        ) {
            $industry = $data['industry_type_other'];
        }

        // بيانات الأعمدة المباشرة
        $coreData = [
            'business_name' => $data['business_name'],
            'industry_type' => $industry,
            'owner_name' => $data['owner_name'],
            'owner_email' => $data['owner_email'],
            'owner_phone' => $data['owner_phone'] ?? null,
        ];

        // form_data: كل مدخلات النموذج (عدا التوكن + حقول الملفات)
        $formData = $request->except([
            '_token',
            'business_licenses',
            'supporting_documents',
            'industry_type_options',
        ]);

        // =========================
        // حفظ الملفات في أعمدة JSON
        // =========================
        $licensesPaths = [];
        $supportingPaths = [];

        // business_licenses[]
        if ($request->hasFile('business_licenses')) {
            foreach ($request->file('business_licenses') as $file) {
                if (!$file || !$file->isValid()) {
                    continue;
                }

                $licensesPaths[] = $file->store('applications/licenses', 'public');
            }
        }

        // supporting_documents[]
        if ($request->hasFile('supporting_documents')) {
            foreach ($request->file('supporting_documents') as $file) {
                if (!$file || !$file->isValid()) {
                    continue;
                }

                $supportingPaths[] = $file->store('applications/supporting', 'public');
            }
        }

        if ($token) {
            // تحديث طلب موجود عبر رابط إعادة التعبئة
            $application = BusinessApplication::where('resubmit_token', $token)->firstOrFail();

            if ($application->resubmit_expires_at && Carbon::now()->gt($application->resubmit_expires_at)) {
                return back()->withErrors(['link' => 'Resubmission link expired.']);
            }

            $application->fill($coreData);
            $application->form_data = $formData;

            // في التعديل نسمح باستبدال الملفات السابقة (إن رُفعت ملفات جديدة)
            if (!empty($licensesPaths)) {
                $application->licenses_paths = $licensesPaths;
            }
            if (!empty($supportingPaths)) {
                $application->supporting_documents_paths = $supportingPaths;
            }

            $application->status = 'pending';
            $application->interpolation = 'completed'; // أو حسب منطقك
            $application->resubmit_token = null;
            $application->resubmit_expires_at = null;
            $application->save();
        } else {
            // إنشاء الطلب الجديد
            $application = BusinessApplication::create($coreData + [
                'status' => 'pending',
                'form_data' => $formData,
                'licenses_paths' => $licensesPaths,
                'supporting_documents_paths' => $supportingPaths,
            ]);

            // 1) إنشاء سجل الإشعار
            // جهّز المصفوفات أولاً
            $title = [
                'ar' => 'تم تقديم طلب انضمام جديد',
                'en' => 'New onboarding application submitted',
            ];

            $body = [
                'ar' => 'قدمت منشأة ' . $application->business_name . ' طلب انضمام.',
                'en' => $application->business_name . ' submitted an onboarding request.',
            ];

            $data = [
                'action_url' => url('/superadmin/applications/' . $application->id),
                'application_id' => $application->id,
            ];

            // 1) إنشاء سجل الإشعار مع تحويل صريح لـ JSON
            $notification = Notification::create([
                'application_id' => $application->id,
                'category' => 'new_application',

                // نخزنها كنص JSON بشكل صريح
                'title' => json_encode($title, JSON_UNESCAPED_UNICODE),
                'body' => json_encode($body, JSON_UNESCAPED_UNICODE),
                'data' => json_encode($data, JSON_UNESCAPED_UNICODE),

                'requires_action' => true,
            ]);


            // 2) جلب كل مستخدمي السوبر أدمن
            $admins = \App\Models\User::where('role', 'sa')->get();

            // 3) ربطهم بالمستلمين
            foreach ($admins as $admin) {
                NotificationRecipient::create([
                    'notification_id' => $notification->id,
                    'user_id' => $admin->id,
                ]);
            }

        }

        return redirect()
            ->route('application.success')
            ->with('success', [
                'title' => [
                    'en' => 'Application submitted',
                    'ar' => 'تم إرسال الطلب',
                ],
                'detail' => [
                    'en' => 'Your application has been submitted and will be reviewed.',
                    'ar' => 'تم استلام طلبك وسيتم مراجعته قريباً.',
                ],
                'level' => 'success',
            ]);

    }

    /**
     * صفحة نجاح إرسال الطلب
     */
    public function success()
    {
        return view('application-onboarding::public.success');
    }

    /**
     * عرض نموذج الاستيفاء (رفع الوثائق/تصحيح بيانات الاتصال) عبر التوكن
     */
    public function interpolationForm(string $token)
    {
        $application = BusinessApplication::where('resubmit_token', $token)->firstOrFail();

        if ($application->resubmit_expires_at && Carbon::now()->gt($application->resubmit_expires_at)) {
            abort(410, 'Resubmission link expired.');
        }

        return view('application-onboarding::public.interpolation-form', [
            'application' => $application,
            'requiredDocs' => (array) ($application->interpolation_required_docs ?? []),
            'token' => $token,
            'note' => $application->interpolation_note,
            'corrections' => (array) ($application->interpolation_contact_corrections ?? []),
        ]);
    }

    /**
     * استلام ملفات الاستيفاء وتصحيحات بيانات الاتصال
     */
    public function interpolationSubmit(Request $request, string $token)
    {
        $application = BusinessApplication::where('resubmit_token', $token)->firstOrFail();

        // صلاحية الرابط
        if ($application->resubmit_expires_at && Carbon::now()->gt($application->resubmit_expires_at)) {
            return back()->withErrors(['link' => 'Resubmission link expired.']);
        }

        // المستندات المطلوبة من السوبر أدمن
        $required = (array) ($application->interpolation_required_docs ?? []);

        // قواعد رفع الملفات
        $rules = [];
        foreach ($required as $idx => $_) {
            $rules["files.$idx"] = 'required|file|max:10240';
        }

        // إن طُلِب تصحيح الإيميل/الهاتف أضف قواعدهما
        $corrections = (array) ($application->interpolation_contact_corrections ?? []);
        if (in_array('owner_email', $corrections, true)) {
            $rules['fix_owner_email'] = 'required|email|max:255';
        }
        if (in_array('owner_phone', $corrections, true)) {
            $rules['fix_owner_phone'] = 'required|string|max:40';
        }

        $request->validate($rules);

        // تخزين الملفات مع التسميات
        $uploaded = [];
        foreach ($required as $idx => $label) {
            $path = $request->file("files.$idx")->store('applications/interpolation', 'public');
            $uploaded[] = ['label' => $label, 'path' => $path];
        }

        // تطبيق التصحيحات إن وُجدت
        if ($request->filled('fix_owner_email')) {
            $application->owner_email = (string) $request->input('fix_owner_email');
        }
        if ($request->filled('fix_owner_phone')) {
            $application->owner_phone = (string) $request->input('fix_owner_phone');
        }

        // دمج المرفوعات وتحديث الحالة
        $old = (array) ($application->interpolation_uploaded_files ?? []);
        $application->interpolation_uploaded_files = array_values(array_merge($old, $uploaded));
        $application->status = 'pending';
        $application->interpolation = 'completed';

        // إنهاء صلاحية الرابط دون حذف التوكن لمنع مشاكل إعادة الطلب
        $application->resubmit_expires_at = Carbon::now();

        $application->save();

        /*
         * ========== NEW: إشعار استيفاء الطلب ==========
         */
        $title = [
            'ar' => 'تم استيفاء طلب منشأة',
            'en' => 'Application interpolation completed',
        ];

        $body = [
            'ar' => 'قامت منشأة ' . ($application->business_name ?: 'غير معروفة') . ' برفع مستندات الاستيفاء المطلوبة.',
            'en' => ($application->business_name ?: 'The business') . ' has uploaded the requested interpolation documents.',
        ];

        $data = [
            'action_url' => url('/superadmin/applications/' . $application->id),
            'application_id' => $application->id,
            'type' => 'interpolation_completed',
        ];

        $notification = Notification::create([
            'application_id' => $application->id,
            'category' => 'interpolation_completed', // كاتيجوري مميز للتبويب الجديد
            'title' => json_encode($title, JSON_UNESCAPED_UNICODE),
            'body' => json_encode($body, JSON_UNESCAPED_UNICODE),
            'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'requires_action' => true,
        ]);

        // إرسال الإشعار لكل السوبرأدمن (role = sa مثلاً)
        $admins = \App\Models\User::where('role', 'sa')->get();

        foreach ($admins as $admin) {
            NotificationRecipient::create([
                'notification_id' => $notification->id,
                'user_id' => $admin->id,
            ]);
        }
        // ========== END NEW ==========

        return redirect()->route('interpolation.success');
    }

    /**
     * صفحة نجاح الاستيفاء
     */
    public function interpolationSuccess()
    {
        return view('application-onboarding::public.interpolation-success');
    }
}
