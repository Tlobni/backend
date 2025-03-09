@extends('layouts.main')

@section('title')
    {{ __('Clients') }}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h4>@yield('title')</h4>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first"></div>
        </div>
    </div>
@endsection

@section('content')
    <section class="section">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="userTable" class="table table-bordered table-striped"
                                        data-toggle="table" data-search="true" data-show-columns="true"
                                        data-pagination="true" data-side-pagination="server"
                                        data-show-refresh="true" data-unique-id="id" data-buttons-class="primary"
                                        data-show-toggle="true" data-fixed-columns="true"
                                        data-fixed-number="1" data-fixed-right-number="1" data-trim-on-search="false"
                                        data-responsive="true" data-sort-name="id" data-sort-order="desc"
                                        data-escape="true"
                                        data-pagination-successively-size="3" data-query-params="queryParamsClient" data-table="users" data-status-column="deleted_at"
                                        data-show-export="true" data-export-options='{"fileName": "client-list","ignoreColumn": ["operate"]}' data-export-types="['pdf','json', 'xml', 'csv', 'txt', 'sql', 'doc', 'excel']"
                                        data-url="{{ url('customer/show') }}"
                                        data-mobile-responsive="true">
                                     <thead class="thead-dark">
                                     <tr>
                                         <th scope="col" data-field="id" data-sortable="true">{{ __('ID') }}</th>
                                         <th scope="col" data-field="profile" data-formatter="imageFormatter">{{ __('Profile') }}</th>
                                         <th scope="col" data-field="name" data-sortable="true">{{ __('Full Name') }}</th>
                                         <th scope="col" data-field="email" data-sortable="true">{{ __('Email') }}</th>
                                         <th scope="col" data-field="mobile" data-sortable="true">{{ __('Mobile') }}</th>
                                         <th scope="col" data-field="gender" data-sortable="true">{{ __('Gender') }}</th>
                                         <th scope="col" data-field="location" data-sortable="true">{{ __('Location') }}</th>
                                         <th scope="col" data-field="is_verified" data-formatter="verifiedFormatter" data-sortable="true">{{ __('Verified') }}</th>
                                         <th scope="col" data-field="status" data-formatter="statusSwitchFormatter" data-sortable="false">{{ __('Status') }}</th>
                                         <th scope="col" data-field="operate" data-escape="false" data-align="center" data-sortable="false" data-events="userEvents">{{ __('Action') }}</th>
                                     </tr>
                                     </thead>
                                 </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('js')
<script>
    function queryParamsClient(p) {
        return {
            limit: p.limit,
            sort: p.sort,
            order: p.order,
            offset: p.offset,
            search: p.search,
            role: 'Client'
        };
    }
    
    // Formatter for verified status
    function verifiedFormatter(value, row) {
        if (value === 1) {
            return '<span class="badge bg-success">{{ __("Yes") }}</span>';
        } else {
            return '<span class="badge bg-danger">{{ __("No") }}</span>';
        }
    }
    
    // Formatter for subscription status
    function subscriptionFormatter(value, row) {
        if (value === 1) {
            return '<span class="badge bg-success">{{ __("Yes") }}</span>';
        } else {
            return '<span class="badge bg-danger">{{ __("No") }}</span>';
        }
    }
    
    // Formatter for profile image
    function imageFormatter(value, row) {
        if (value) {
            return '<img src="' + value + '" class="rounded-circle" width="50" height="50">';
        } else {
            return '<img src="{{ asset('assets/images/faces/1.jpg') }}" class="rounded-circle" width="50" height="50">';
        }
    }
    
    // Formatter for status switch
    function statusSwitchFormatter(value, row) {
        let checked = value ? 'checked' : '';
        return '<div class="form-check form-switch">' +
            '<input class="form-check-input status-switch" type="checkbox" ' + checked + ' data-id="' + row.id + '">' +
            '</div>';
    }
    
    // User events for action buttons
    window.userEvents = {
        'click .edit-user': function (e, value, row, index) {
            window.location.href = "{{ url('customer') }}/" + row.id + "/edit";
        },
        'click .delete-user': function (e, value, row, index) {
            if (confirm("{{ __('Are you sure you want to delete this user?') }}")) {
                $.ajax({
                    url: "{{ url('customer') }}/" + row.id,
                    type: 'DELETE',
                    data: {
                        "_token": "{{ csrf_token() }}"
                    },
                    success: function (result) {
                        if (result.error === false) {
                            $('#userTable').bootstrapTable('refresh');
                        }
                    }
                });
            }
        }
    };
    
    $(document).ready(function () {
        // Initialize the table
        $('#userTable').bootstrapTable();
        
        // Handle status switch change
        $(document).on('change', '.status-switch', function () {
            let id = $(this).data('id');
            let status = $(this).prop('checked') ? 1 : 0;
            
            $.ajax({
                url: "{{ url('customer/update') }}",
                type: 'POST',
                data: {
                    "_token": "{{ csrf_token() }}",
                    "id": id,
                    "status": status
                },
                success: function (result) {
                    if (result.error === false) {
                        $('#userTable').bootstrapTable('refresh');
                    }
                }
            });
        });
    });
</script>
@endsection 