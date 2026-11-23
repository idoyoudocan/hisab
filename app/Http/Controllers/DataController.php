<?php

namespace App\Http\Controllers;

use App\Models\AdminNotice;
use App\Support\BusinessData;
use App\Support\Hisab;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Shopkeeper's own data: backup download, restore from a backup file,
 * reset (fresh start), and dismissing admin notices.
 * Everything is scoped to the current user's business.
 */
class DataController extends Controller
{
    /** Download the whole business as one JSON backup file. */
    public function backup(): StreamedResponse
    {
        $business = Hisab::requireBusiness();
        $payload = BusinessData::export($business);
        $filename = 'hisab-backup-'.now()->format('Y-m-d').'.json';

        return response()->streamDownload(function () use ($payload) {
            echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }, $filename, ['Content-Type' => 'application/json; charset=UTF-8']);
    }

    /** Restore from an uploaded backup file (same business only). */
    public function restore(Request $request): RedirectResponse
    {
        $request->validate([
            'backup' => ['required', 'file', 'mimes:json,txt', 'max:10240'],
        ]);

        $business = Hisab::requireBusiness();
        $decoded = json_decode($request->file('backup')->get(), true);

        try {
            $counts = BusinessData::restore($business, $decoded);
        } catch (RuntimeException $e) {
            return back()->withErrors(['backup' => $e->getMessage()]);
        } catch (Throwable) {
            return back()->withErrors(['backup' => 'ব্যাকআপ ফাইলটি ভাঙা বা সঠিক নয় — কিছুই বদলানো হয়নি।']);
        }

        $parts = [];
        if (($counts['sales'] ?? 0) > 0) {
            $parts[] = $counts['sales'].'টি বিক্রি';
        }
        if (($counts['products'] ?? 0) > 0) {
            $parts[] = $counts['products'].'টি মাল';
        }
        if (($counts['customers'] ?? 0) > 0) {
            $parts[] = $counts['customers'].' জন কাস্টমার';
        }
        $summary = $parts !== [] ? ' ফিরেছে: '.implode(', ', $parts).'।' : '';

        return back()->with('status', 'ব্যাকআপ থেকে ডেটা ফেরানো হয়েছে।'.$summary);
    }

    /** Wipe all shop data and start fresh (typed business name to confirm). */
    public function reset(Request $request): RedirectResponse
    {
        $business = Hisab::requireBusiness();
        $data = $request->validate([
            'confirm_name' => ['required', 'string', 'max:150'],
        ]);

        if (trim($data['confirm_name']) !== $business->name) {
            return back()->withErrors(['confirm_name' => 'দোকানের নামটি ঠিকভাবে লিখুন — নাম না মিললে রিসেট হবে না।']);
        }

        BusinessData::resetData($business);

        return back()->with('status', 'সব ডেটা মুছে ফেলা হয়েছে — এখন নতুন করে শুরু করতে পারবেন। দোকানের তথ্য, লগইন আর প্ল্যান আগের মতোই আছে।');
    }

    /** Mark one admin notice as read (dismisses it from the dashboard). */
    public function markNoticeRead(AdminNotice $notice): RedirectResponse
    {
        $business = Hisab::requireBusiness();
        if ((int) $notice->business_id !== (int) $business->id) {
            abort(404);
        }
        if ($notice->read_at === null) {
            $notice->update(['read_at' => now()]);
        }

        return back();
    }
}
