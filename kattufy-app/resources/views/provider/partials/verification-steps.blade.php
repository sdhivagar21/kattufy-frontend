@use('App\Modules\Verification\Enums\VerificationState')
{{--
    Details → Submitted → Admin review → Decision, read from the provider's
    computed VerificationState (the same records the admin works on).
--}}
@php
    $state ??= VerificationState::NotSubmitted;
    // How many steps are finished, and how the last step reads.
    [$done, $result, $tone] = match ($state) {
        VerificationState::NotSubmitted => [0, __('provider.vstep_decision'), 'neutral'],
        VerificationState::PendingReview => [2, __('provider.vstep_decision'), 'neutral'],
        VerificationState::ChangesRequested, VerificationState::NeedsUpdate => [3, __('provider.vstep_action'), 'warning'],
        VerificationState::Approved => [4, __('provider.vstep_approved'), 'success'],
        VerificationState::Suspended => [3, __('provider.vstep_suspended'), 'danger'],
    };
    $steps = [__('provider.vstep_details'), __('provider.vstep_submitted'), __('provider.vstep_review'), $result];
    $finalStyle = [
        'success' => 'bg-forest text-white',
        'warning' => 'bg-amber text-ink ring-4 ring-amber/20',
        'danger' => 'bg-danger text-white ring-4 ring-danger/20',
        'neutral' => 'bg-surface-2 text-fg-subtle ring-1 ring-line-strong',
    ][$tone];
@endphp
<ol class="grid grid-cols-4 gap-2 {{ $class ?? '' }}" aria-label="{{ __('provider.vsteps_label') }}">
    @foreach ($steps as $i => $label)
        @php
            $n = $i + 1;
            $isFinal = $n === 4;
            $finished = $n <= $done && ! ($isFinal && $tone !== 'success');
            $current = ! $finished && ($n === $done + 1 || ($isFinal && $done >= 3));
        @endphp
        <li class="relative flex min-w-0 flex-col items-center gap-2 text-center" @if ($current) aria-current="step" @endif>
            @unless ($loop->last)
                <span @class(['absolute top-3.5 left-[calc(50%+1.1rem)] h-0.5 w-[calc(100%-2.2rem+0.5rem)] rounded-full', 'bg-forest' => $n <= $done, 'bg-line-strong' => $n > $done]) aria-hidden="true"></span>
            @endunless
            <span @class([
                'relative flex size-7 items-center justify-center rounded-full text-xs font-bold',
                'bg-forest text-white' => $finished,
                $finalStyle => $isFinal && ! $finished && $done >= 3,
                'bg-amber text-ink ring-4 ring-amber/20' => $current && ! $isFinal,
                'bg-surface-2 text-fg-subtle ring-1 ring-line-strong' => ! $finished && ! $current,
            ])>
                @if ($finished)
                    <x-ki name="check" class="size-4" />
                @elseif ($isFinal && $tone === 'warning')
                    <x-ki name="alert" class="size-4" />
                @elseif ($isFinal && $tone === 'danger')
                    <x-ki name="x" class="size-4" />
                @elseif ($current && $state === VerificationState::PendingReview)
                    <span class="size-2 rounded-full bg-ink motion-safe:animate-pulse"></span>
                @else
                    {{ $n }}
                @endif
            </span>
            <span @class(['text-[0.75rem] leading-tight font-medium sm:text-sm', 'text-fg' => $finished || $current, 'text-fg-subtle' => ! $finished && ! $current])>{{ $label }}</span>
        </li>
    @endforeach
</ol>
