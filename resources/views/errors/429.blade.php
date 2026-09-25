@extends('errors.layout')

@php($retryAfter = (int) ($exception->getHeaders()['Retry-After'] ?? 0))

@section('code', '429')
@section('title', 'Terlalu banyak percobaan')
@section('message')
    Demi keamanan, permintaan dari perangkat ini dibatasi sementara.
    {{ $retryAfter > 0 ? 'Coba lagi dalam '.\Carbon\CarbonInterval::seconds($retryAfter)->cascade()->forHumans().'.' : 'Tunggu sebentar lalu coba lagi.' }}
@endsection
