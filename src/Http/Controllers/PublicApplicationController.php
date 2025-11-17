<?php

namespace Slsabil\ApplicationOnboarding\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Slsabil\ApplicationOnboarding\Models\BusinessApplication;
use Slsabil\ApplicationOnboarding\Models\FormField;

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

        return view('application-onboarding::public.apply', [
            'application' => $application,
            'fields'      => $fields,
        ]);
    }

    /**
     * إرسال نموذج التقديم (إنشاء جديد أو تحديث نفس السجل عند وجود توكن)
     */
    public function submit(Request $request, ?string $token = null)
    {
        // فحص الحقول الأساسية فقط، والباقي يخزن في form_data
        $data = $request->validate([
            'business_name'        => 'required|string|max:255',
            'industry_type'        => 'nullable|string|max:255',
            'industry_type_other'  => 'nullable|string|max:255',
            'owner_name'           => 'required|string|max:255',
            'owner_email'          => 'required|email|max:255',
            'owner_phone'          => 'nullable|string|max:40',
            'accept_policies'      => 'required|string|in:yes',
        ], [
            'accept_policies.required' => __('You must accept the application policies.'),
            'accept_policies.in'       => __('You must accept the application policies.'),
        ]);

        // معالجة خيار "Other" لنوع الصناعة
        $industry = $data['industry_type'] ?? null;
        if (($industry === 'Other' || $industry === 'أخرى' || $industry === 'اخرى') && !empty($data['industry_type_other'])) {
            $industry = $data['industry_type_other'];
        }

        // بيانات الأعمدة المباشرة
        $coreData = [
            'business_name' => $data['business_name'],
            'industry_type' => $industry,
            'owner_name'    => $data['owner_name'],
            'owner_email'   => $data['owner_email'],
            'owner_phone'   => $data['owner_phone'] ?? null,
        ];

        // form_data: كل مدخلات النموذج (عدا التوكن وحقل الموافقة)
        $formData = $request->except([
            '_token',
            'accept_policies',
        ]);

        if ($token) {
            // تحديث طلب موجود عبر رابط إعادة التعبئة
            $application = BusinessApplication::where('resubmit_token', $token)->firstOrFail();

            if ($application->resubmit_expires_at && Carbon::now()->gt($application->resubmit_expires_at)) {
                return back()->withErrors(['link' => 'Resubmission link expired.']);
            }

            $application->fill($coreData);
            $application->form_data = $formData;
            $application->status = 'pending';
            $application->interpolation = 'completed'; // أو حسب منطقك
            $application->resubmit_token = null;
            $application->resubmit_expires_at = null;
            $application->save();
        } else {
            // إنشاء طلب جديد
            BusinessApplication::create($coreData + [
                'status'    => 'pending',
                'form_data' => $formData,
            ]);
        }

        return redirect()
            ->route('application.success')
            ->with('success', __('Application submitted successfully.'));
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
            'application'  => $application,
            'requiredDocs' => (array) ($application->interpolation_required_docs ?? []),
            'token'        => $token,
            'note'         => $application->interpolation_note,
            'corrections'  => (array) ($application->interpolation_contact_corrections ?? []),
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
