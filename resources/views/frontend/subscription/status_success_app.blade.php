@extends('frontend.layouts.app5')
@section('title', trans('labels.subscription.payment_status').' | '.app_name())

@push('after-styles')
    <style>
        input[type="radio"] {
            display: inline-block !important;
        }
    </style>
@endpush

@section('content')
    <section id="checkout" class="checkout-section">
        <div class="container">
            <div class="section-title mb45 headline text-center">
                @if(session()->has('success'))
                    <h2>  {{session('success')}}</h2>
                @endif
            </div>
        </div>
    </section>
@endsection
