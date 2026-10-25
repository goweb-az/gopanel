<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gopanel\Admins;

use App\Http\Controllers\GoPanelController;
use App\Http\Middleware\Gopanel\RequireAdminPin;
use App\Http\Requests\Gopanel\Admin\AdminPinConfirmRequest;
use App\Http\Requests\Gopanel\Admin\ProfilePinRequest;
use App\Services\Gopanel\Admins\AdminPinService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin PIN-i: öz PIN-ini idarə etmək və həssas əməliyyatdan əvvəl təsdiq.
 *
 * `ProfileController`-ə əlavə edilmədi: onun konstruktor imzası törəmə
 * layihələrdə genişləndirilə bilər - yeni asılılıq üçün ayrıca controller.
 * Bütün qərarlar `AdminPinService`-dədir, burada yalnız cavab formalaşır.
 */
class AdminPinController extends GoPanelController
{
    public function __construct(private readonly AdminPinService $pins)
    {
        parent::__construct();
    }

    /** Profil → «PIN kodum» səhifəsi. */
    public function edit(): View
    {
        $item = auth('gopanel')->user();

        return view('gopanel.pages.profile.pin', [
            'item'       => $item,
            'hasPin'     => $item->hasPin(),
            'updatedAt'  => $item->pin_updated_at?->format('d.m.Y H:i'),
            'enabled'    => $this->pins->enabled(),
            'minLength'  => $this->pins->minLength(),
            'maxLength'  => $this->pins->maxLength(),
            'ttlMinutes' => $this->pins->ttlMinutes(),
        ]);
    }

    public function update(ProfilePinRequest $request): JsonResponse
    {
        $item = auth('gopanel')->user();

        if ($request->boolean('clear')) {
            $this->pins->forget($item);
            $message = 'PIN geri alındı.';
        } else {
            $this->pins->set($item, (string) $request->input('pin'));
            $message = 'PIN yadda saxlanıldı.';
        }

        $this->response['redirect'] = route('gopanel.profile.pin.index');
        $this->success_response([], $message);

        return $this->response_json();
    }

    /**
     * Adi GET keçidi üçün təsdiq səhifəsi (`RequireAdminPin` bura yönləndirir).
     *
     * Təsdiq lazım deyilsə (mexanizm sönük, PIN yoxdur, artıq təsdiqlənib)
     * səhifə göstərilmir - birbaşa hədəfə keçilir.
     */
    public function prompt(Request $request): View|RedirectResponse
    {
        $admin = auth('gopanel')->user();

        if (!$this->pins->needsConfirmation($admin)) {
            return redirect()->to($this->pullIntended($request));
        }

        return view('gopanel.pages.security.pin-prompt', [
            'backUrl' => (string) $request->session()->get(RequireAdminPin::BACK_KEY, route('gopanel.index')),
        ]);
    }

    /**
     * PIN təsdiqi - həm modal (AJAX), həm də təsdiq səhifəsi üçün.
     *
     * Uğurda JSON cavabında `redirect` (səhifədən gəlibsə hədəf URL) verilir;
     * modal isə dayandırılmış sorğunu özü təkrarlayır.
     */
    public function confirm(AdminPinConfirmRequest $request): JsonResponse|RedirectResponse
    {
        $result = $this->pins->attemptConfirmation(auth('gopanel')->user(), (string) $request->input('pin'));

        if (!$request->expectsJson() && !$request->ajax()) {
            return $result['ok']
                ? redirect()->to($this->pullIntended($request))
                : back()->withErrors(['pin' => $result['message']]);
        }

        if (!$result['ok']) {
            return response()->json(['status' => 'error', 'message' => $result['message']], $result['status']);
        }

        $this->response['redirect'] = $request->session()->has(RequireAdminPin::INTENDED_KEY)
            ? $this->pullIntended($request)
            : false;
        $this->success_response([], $result['message']);

        return $this->response_json();
    }

    /** Sessiyadakı hədəf (bir dəfəlik) - yoxdursa panelin ana səhifəsi. */
    private function pullIntended(Request $request): string
    {
        return (string) $request->session()->pull(RequireAdminPin::INTENDED_KEY, route('gopanel.index'));
    }
}
