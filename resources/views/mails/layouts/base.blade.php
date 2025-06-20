@extends('layouts.email')

@php

$socialMedia = [
['label' => 'Facebook', 'link' => 'https://www.facebook.com/ainetindia', 'icon' => 'facebook.svg', 'class' => 'facebook'],
['label' => 'Instagram', 'link' => 'https://www.instagram.com/ainetindia', 'icon' => 'instagram.svg', 'class' => 'instagram'],
['label' => 'Twitter', 'link' => 'https://x.com/ainetindia', 'icon' => 'twitter.svg', 'class' => 'twitter'],
['label' => 'Youtube', 'link' => 'https://www.youtube.com/AINETIndia', 'icon' => 'youtube.svg', 'class' => 'youtube'],
];
@endphp

@section('email_css')
<style type="text/css">
    a {
        text-decoration: none;
        color: #007BC3
    }

    .text__primary {
        color: #007BC3 !important
    }

    .bg__primary {
        background-color: #007BC3 !important
    }
</style>
@endsection

@section('title', $title ?? ($subject ?? null))

@section('body')

@yield('content')

 @if (isset($member))
        <x-divider></x-divider>
        <x-member name="{{ $member->name }}"
            description="{{ $member->position }} at <strong class='text__primary'>{{ $member->company }}</strong>"
            avatar="{{ $member->avatar }}" team="{{ $member->team }}">
        </x-member>
    @endif

    @if (isset($opt))
        <x-divider></x-divider>
        <p class="text__center">
            {{ $opt->message }} If not, you can
            <a href="{{ $opt->unsubscribe }}" target="_blank">
                <strong class="text__primary">
                    unsubscribe
                </strong>
            </a> from it.
        </p>
    @endif
@endsection