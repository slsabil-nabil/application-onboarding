<?php

namespace Slsabil\ApplicationOnboarding\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Slsabil\ApplicationOnboarding\Models\BusinessApplication;
use App\Mail\DocumentsRequestMail;
use App\Jobs\SendSmsJob;
use App\Services\Sms\SmsTemplate;

class SuperAdminApplicationsController extends Controller
{
    /**
     * قائمة طلبات الانضمام مع فلتر الحالة
     */
    public function applicationsIndex(Request $request)
    {
        $status = $request->query('status', 'pending');

        $applications = BusinessApplication::when($status === 'pending', function ($query) {
                return $query->where('status', 'pending');
            })
            ->when($status === 'accepted', function ($query) {
                return $query->where('status', 'accepted');
            })
            ->when($status === 'rejected', function ($query) {
                return $query->where('status', 'rejected');
            })
            ->when($status === 'interpolation', function ($query) {
                return $query->where('status', 'interpolation');
            })
            ->latest()
            ->get();

        return view('application-onboarding::superadmin.applications.index', [
            'applications'  => $applications,
            'currentStatus' => $status,
        ]);
    }

    /**
     * تحديث حالة الاستيفاء لسجل معين (يحوله إلى pending)
     */
    public function updateInterpolationStatus(Request $request, BusinessApplication $application)
    {
        $application->interpolation = 'pending';
        $application->save();

        return redirect()->route('superadmin.applications.index')->with('success', [
            'title'  => ['en' => 'Interpolation started', 'ar' => 'تم بدء الاستيفاء'],
            'detail' => ['en' => 'Application moved to Interpolation.', 'ar' => 'تم نقل الطلب إلى الاستيفاء.'],
            'level'  => 'success',
        ]);
    }

    /**
     * عرض صفحة إعداد الاستيفاء (للسوبر أدمن)
     */
    public function showInterpolationPage(BusinessApplication $application)
    {
        return view('application-onboarding::superadmin.applications.interpolation', [
            'application' => $application,
        ]);
    }

    /**
     * إرسال طلب الوثائق/التصحيحات إلى المنشأة (إيميل + SMS)
     */
    public function submitDocuments(Request $request, BusinessApplication $application)
    {
        $request->validate([
            'docs'                 => 'nullable|array',
            'docs.*'               => 'nullable|string|max:255',
            'interpolation_note'   => 'nullable|string|max:500',
            'request_corrections'  => 'nullable|array',
            'request_corrections.*'=> 'in:owner_email,owner_phone',
        ]);

        // تنظيف الوثائق الفارغة
        $documents = collect($request->input('docs', []))
            ->map(fn($v) => is_string($v) ? trim($v) : $v)
            ->filter(fn($v) => !empty($v))
            ->values()
            ->all();

        $corrections = $request->input('request_corrections', []);

        // يجب تعبئة قسم واحد على الأقل
        if (count($documents) === 0 && count($corrections) === 0) {
            return back()
                ->withErrors(['form' => 'يرجى تعبئة قسم الوثائق أو تحديد تصحيحات معلومات الاتصال.'])
                ->withInput();
        }

        $application->status = 'interpolation';
        $application->interpolation = 'pending';
        $application->resubmit_token = Str::uuid()->toString();
        $application->resubmit_expires_at = Carbon::now()->addDays(7);
        $application->interpolation_required_docs = $documents;
        $application->interpolation_note = $request->string('interpolation_note')->value();
        $application->interpolation_contact_corrections = $corrections;
        $application->save();

        $resubmitUrl = route('interpolation.show', ['token' => $application->resubmit_token]);

        // إرسال الإيميل
        Mail::to($application->owner_email)->send(
            new DocumentsRequestMail(
                docs: $documents,
                applicationFormLink: $resubmitUrl,
                note: $application->interpolation_note,
                corrections: $corrections
            )
        );

        // إرسال SMS (اختياري حسب إعدادات النظام)
        if (!empty($application->owner_phone) && config('sms.enabled', true)) {
            $to = preg_replace('/\s+/', '', $application->owner_phone);
            if (!str_starts_with($to, '+')) {
                $cc = rtrim(config('sms.default_country_code', '+967'));
                $to = $cc . ltrim($to, '0+');
            }

            $locale = data_get($application->form_data, '_locale', config('app.locale', 'ar'));

            $body = SmsTemplate::render('manager', 'documents_request', [
                'business' => $application->business_name ?? ($locale === 'ar' ? 'منشأتك' : 'your business'),
                'link'     => $resubmitUrl,
            ], $locale);

            dispatch((new SendSmsJob($to, $body))->onQueue(config('sms.queue', 'sms')));

            Log::info('intp_sms_queued', [
                'app_id' => $application->id,
                'to'     => $to,
                'lang'   => $locale,
            ]);
        }

        return redirect()
            ->route('superadmin.applications.index', ['status' => 'interpolation'])
            ->with('success', [
                'title'  => ['en' => 'Request sent', 'ar' => 'تم إرسال الطلب'],
                'detail' => ['en' => 'Email/SMS sent to applicant.', 'ar' => 'تم إرسال بريد ورسالة SMS للمنشأة.'],
                'level'  => 'success',
            ])
            ->with('missingDocuments', $documents);
    }

    /**
     * تغيير حالة الطلب إلى "appeal" (إن أحببت استخدامها)
     */
    public function sendDocumentsRequest(BusinessApplication $application)
    {
        $application->status = 'appeal';
        $application->save();

        return redirect()
            ->route('superadmin.applications.index', ['status' => 'appeal'])
            ->with('success', [
                'title'  => ['en' => 'Document request sent', 'ar' => 'تم إرسال طلب الوثائق'],
                'detail' => ['en' => 'Status updated to Appeal.', 'ar' => 'تم تحديث الحالة إلى الاستئناف.'],
                'level'  => 'success',
            ]);
    }
}
