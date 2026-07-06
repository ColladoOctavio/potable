@php($money = fn ($v) => '$ '.number_format((float) $v, 2, ',', '.'))
<div class="row g-3 mb-4">
    @foreach($items as $label => $value)
        <div class="col-sm-6 col-xl-3">
            <div class="metric-card p-3">
                <div class="metric-label">{{ $label }}</div>
                <div class="metric-value">{{ is_numeric($value) ? $money($value) : $value }}</div>
            </div>
        </div>
    @endforeach
</div>
