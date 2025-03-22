@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0">Account Disabled (403 Forbidden)</h4>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <i class="fa fa-ban fa-4x text-danger mb-3"></i>
                        <h5 class="text-danger">{{ $exception->getMessage() ?: 'Your account has been disabled' }}</h5>
                    </div>
                    
                    <div class="alert alert-warning">
                        <p>Your account has been disabled by an administrator. This could be due to one of the following reasons:</p>
                        <ul>
                            <li>Violation of terms of service</li>
                            <li>Account inactivity</li>
                            <li>Administrative action</li>
                        </ul>
                    </div>
                    
                    <p class="text-center">
                        If you believe this is a mistake, please contact support for assistance.
                    </p>
                    
                    <div class="text-center mt-4">
                        <a href="{{ route('login') }}" class="btn btn-primary">Return to Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 