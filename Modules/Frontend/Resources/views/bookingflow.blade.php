@extends('frontend::layouts.master')

@section('content')


<x-service_section :categories="$categories" :allServicesCount="$allServicesCount" />
<x-expert_section/>
<x-bookingdetails_section/>
<x-ratenow_section/>

@endsection