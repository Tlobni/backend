@extends('layouts.main')

@section('title')
    {{ __('Experts') }}
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
                                    <table id="userTable" class="table table-bordered table-striped" data-toggle="table"
                                        data-search="true" data-show-columns="true" data-pagination="true"
                                        data-side-pagination="server" data-show-refresh="true" data-unique-id="id"
                                        data-buttons-class="primary" data-show-toggle="true" data-fixed-columns="true"
                                        data-fixed-number="1" data-fixed-right-number="1" data-trim-on-search="false"
                                        data-pagination-successively-size="3" data-query-params="queryParamsExpert"
                                        data-table="users" data-status-column="deleted_at" data-show-export="true"
                                        data-export-options='{"fileName": "expert-list","ignoreColumn": ["operate"]}'
                                        data-export-types="['pdf','json', 'xml', 'csv', 'txt', 'sql', 'doc', 'excel']"
                                        data-url="{{ route('customer.list-by-role') }}" data-mobile-responsive="true">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th scope="col" data-field="id" data-sortable="true">{{ __('ID') }}
                                                </th>
                                                <th scope="col" data-field="profile" data-formatter="imageFormatter">
                                                    {{ __('Profile') }}</th>
                                                <th scope="col" data-field="name" data-sortable="true">
                                                    {{ __('Full Name') }}</th>
                                                <th scope="col" data-field="email" data-sortable="true">
                                                    {{ __('Email') }}</th>
                                                <th scope="col" data-field="categories"
                                                    data-formatter="categoriesFormatter" data-sortable="true">
                                                    {{ __('Categories') }}</th>

                                                <th scope="col" data-field="mobile" data-sortable="true">
                                                    {{ __('Mobile') }}</th>
                                                <th scope="col" data-field="gender" data-formatter="genderFormatter" data-sortable="true">
                                                    {{ __('Gender') }}</th>
                                                <th scope="col" data-field="location" data-sortable="true">
                                                    {{ __('Location') }}</th>
                                                <th scope="col" data-field="expertise" data-sortable="true">
                                                    {{ __('Expertise') }}</th>
                                                <th scope="col" data-field="experience" data-sortable="true">
                                                    {{ __('Experience') }}</th>
                                                <th scope="col" data-field="services_count" data-sortable="true">
                                                    {{ __('Services') }}</th>
                                                <th scope="col" data-field="has_active_package"
                                                    data-formatter="packageFormatter" data-sortable="true">
                                                    {{ __('Active Package') }}</th>
                                                <th scope="col" data-field="is_verified"
                                                    data-formatter="verifiedFormatter" data-sortable="true">
                                                    {{ __('Verified') }}</th>
                                                <th scope="col" data-field="is_featured"
                                                    data-formatter="featuredFormatter" data-sortable="true">
                                                    {{ __('Featured') }}</th>
                                                <th scope="col" data-field="status"
                                                    data-formatter="statusSwitchFormatter" data-sortable="false">
                                                    {{ __('Status') }}</th>
                                                <th scope="col" data-field="operate" data-escape="false"
                                                    data-align="center" data-sortable="false" data-events="userEvents">
                                                    {{ __('Action') }}</th>
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

    <!-- Assign Package Modal -->
    <div id="assignPackageModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel1"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="myModalLabel1">{{ __('Assign Packages') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        onclick="resetModal()"></button>
                </div>
                <div class="modal-body">
                    <form class="create-form" action="{{ route('customer.assign.package') }}" method="POST"
                        data-parsley-validate data-success-function="assignApprovalSuccess">
                        @csrf
                        <input type="hidden" name="user_id" id='user_id'>
                        <div id="currency-settings" data-symbol="{{ $currency_symbol ?? '' }}"
                            data-position="{{ $currency_symbol_position ?? '' }}"
                            data-free-ad-listing="{{ $free_ad_listing ?? 0 }}"></div>
                        @if (($free_ad_listing ?? 0) != 1)
                            <div class="form-group row select-package">
                                <div class="col-md-6">
                                    <input type="radio" id="item_package" class="package_type form-check-input"
                                        name="package_type" value="item_listing" required>
                                    <label for="item_package">{{ __('Item Listing Package') }}</label>
                                </div>
                                <div class="col-md-6">
                                    <input type="radio" id="advertisement_package"
                                        class="package_type form-check-input" name="package_type" value="advertisement"
                                        required>
                                    <label for="advertisement_package">{{ __('Advertisement Package') }}</label>
                                </div>
                            </div>
                        @endif
                        <div class="row mt-3" id="item-listing-package-div" style="display: none;">
                            <div class="form-group col-md-12">
                                <label for="package">{{ __('Select Item Listing Package') }}</label>
                                <select name="package_id" class="form-select package" id="item-listing-package"
                                    aria-label="Package">
                                    <option value="" disabled selected>Select Option</option>
                                    @foreach ($itemListingPackage ?? [] as $package)
                                        <option value="{{ $package->id }}" data-details="{{ json_encode($package) }}">
                                            {{ $package->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row mt-3" id="advertisement-package-div"
                            style="{{ ($free_ad_listing ?? 0) == '1' ? 'display: block;' : 'display: none;' }}">
                            <div class="form-group col-md-12">
                                <label for="package">{{ __('Select Advertisement Package') }}</label>
                                <select name="package_id" class="form-select package" id="advertisement-package"
                                    aria-label="Package">
                                    <option value="" disabled selected>Select Option</option>
                                    @foreach ($advertisementPackage ?? [] as $package)
                                        <option value="{{ $package->id }}" data-details="{{ json_encode($package) }}">
                                            {{ $package->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div id="package_details" class="mt-3" style="display: none;">
                            <p><strong>Name:</strong> <span id="package_name"></span></p>
                            <p><strong>Price:</strong> <span id="package_price"></span></p>
                            <p><strong>Final Price:</strong> <span id="package_final_price"></span></p>
                            <p><strong>Limitation:</strong> <span id="package_duration"></span></p>
                        </div>
                        <div class="form-group row payment" style="display: none">
                            <div class="col-md-6">
                                <input type="radio" id="cash_payment" class="payment_gateway form-check-input"
                                    name="payment_gateway" value="cash" required>
                                <label for="cash_payment">{{ __('Cash') }}</label>
                            </div>
                            <div class="col-md-6">
                                <input type="radio" id="cheque_payment" class="payment_gateway form-check-input"
                                    name="payment_gateway" value="cheque" required>
                                <label for="cheque_payment">{{ __('Cheque') }}</label>
                            </div>
                        </div>
                        <div class="form-group cheque mt-3" style="display: none">
                            <label for="cheque">{{ __('Add cheque number') }}</label>
                            <input type="text" id="cheque" class="form-control" name="cheque_number"
                                data-parsley-required="true">
                        </div>
                        <input type="submit" value="{{ __('Save') }}" class="btn btn-primary mt-3">
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        function queryParamsExpert(p) {
            return {
                limit: p.limit,
                sort: p.sort,
                order: p.order,
                offset: p.offset,
                search: p.search,
                role: 'expert'
            };
        }

        // Formatter for gender to display styled badge
        function genderFormatter(value, row) {
            console.log('Gender value:', value, 'Type:', typeof value);
            
            if (!value) return '<span class="badge bg-light-secondary">Not Specified</span>';
            
            // Convert to string and lowercase for case-insensitive comparison
            let gender = String(value).toLowerCase();
            
            if (gender === 'male' || gender === 'm') {
                return '<span class="badge bg-light-primary">Male</span>';
            } else if (gender === 'female' || gender === 'f') {
                return '<span class="badge bg-light-info">Female</span>';
            } else {
                return '<span class="badge bg-light-secondary">' + value + '</span>';
            }
        }

        // Formatter for categories to display names instead of IDs
        function categoriesFormatter(value, row) {
            if (!value) return '';

            // Cache for category data to avoid multiple requests for the same categories
            if (!window.categoryCache) {
                window.categoryCache = {};
            }

            let categoryIds = value.split(',');
            let output = '';

            try {
                // Check if we already have these categories in cache
                let missingIds = categoryIds.filter(id => !window.categoryCache[id]);

                if (missingIds.length > 0) {
                    // Fetch missing category names synchronously
                    $.ajax({
                        url: "{{ route('customer.get-category-names') }}",
                        type: 'GET',
                        async: false, // Important: Must be synchronous for formatter
                        cache: false,
                        data: {
                            ids: value
                        },
                        success: function(response) {
                            if (response.success) {
                                // Update the cache with the new category data
                                Object.assign(window.categoryCache, response.data);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Error fetching category names:', error);
                        }
                    });
                }

                // Build badges from category names using cache
                for (let i = 0; i < categoryIds.length; i++) {
                    let id = categoryIds[i];
                    if (!id || id === "") continue; // Skip empty IDs
                    
                    // Only display the category if it exists in the cache (meaning it hasn't been deleted)
                    if (window.categoryCache[id]) {
                        output += '<span class="badge bg-light-primary me-1">' + window.categoryCache[id] + '</span>';
                    }
                }
            } catch (error) {
                console.error('Error in categoriesFormatter:', error);
                return value; // Fallback to original value on error
            }

            return output;
        }

        // Formatter for package status
        function packageFormatter(value, row) {
            if (value) {
                let packageInfo = row.active_package_name || 'Active Package';
                let badgeClass = 'bg-success';
                let expiryInfo = '';

                if (row.active_package_expiry) {
                    const expiryDate = new Date(row.active_package_expiry);
                    const formattedDate = expiryDate.toLocaleDateString();
                    expiryInfo = ' (Expires: ' + formattedDate + ')';
                } else {
                    expiryInfo = ' (Unlimited)';
                }

                return '<span class="badge ' + badgeClass + '">' + packageInfo + expiryInfo + '</span>';
            } else {
                return '<span class="badge bg-secondary">{{ __('No Package') }}</span>';
            }
        }

        // Formatter for verified status
        function verifiedFormatter(value, row) {
            if (value === 1) {
                return '<span class="badge bg-success">{{ __('Yes') }}</span>';
            } else {
                return '<span class="badge bg-danger">{{ __('No') }}</span>';
            }
        }

        // Formatter for featured status
        function featuredFormatter(value, row) {
            console.log('Featured formatter for user ID:', row.id, 'Value:', value, 'Type:', typeof value, 'Row data:', row);
            
            // Ensure value is treated as a boolean - more comprehensive type checking
            let isFeatured = false;
            
            // Check if we have featured_users in the row data (relationship data)
            if (row.featured_users && (Array.isArray(row.featured_users) && row.featured_users.length > 0)) {
                console.log('User has featured_users relationship data');
                isFeatured = true;
            }
            // Also check the direct is_featured flag
            else if (value === true || value === 1 || value === "true" || value === "1") {
                console.log('User has is_featured flag set to true');
                isFeatured = true;
            }
            
            console.log('Final featured status for user ID ' + row.id + ':', isFeatured);
            
            if (isFeatured) {
                return '<div class="form-check form-switch">' +
                    '<input class="form-check-input featured-toggle" type="checkbox" ' +
                    'data-id="' + row.id + '" checked>' +
                    '</div>';
            } else {
                return '<div class="form-check form-switch">' +
                    '<input class="form-check-input featured-toggle" type="checkbox" ' +
                    'data-id="' + row.id + '">' +
                    '</div>';
            }
        }

        // Formatter for subscription status
        function subscriptionFormatter(value, row) {
            if (value === 1) {
                return '<span class="badge bg-success">{{ __('Yes') }}</span>';
            } else {
                return '<span class="badge bg-danger">{{ __('No') }}</span>';
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
                '<input class="form-check-input update-user-status" id="' + row.id + '" type="checkbox" ' + checked + '>' +
                '</div>';
        }

        // User events for action buttons
        window.userEvents = {
            'click .edit-user': function(e, value, row, index) {
                window.location.href = "{{ url('customer') }}/" + row.id + "/edit";
            },
            'click .delete-user': function(e, value, row, index) {
                console.log('Delete user clicked for row:', row);
                if (confirm("{{ __('Are you sure you want to delete this user?') }}")) {
                    console.log('User confirmed deletion of user ID:', row.id);
                    $.ajax({
                        url: "{{ url('customer/delete') }}/" + row.id,
                        type: 'DELETE',
                        data: {
                            "_token": "{{ csrf_token() }}"
                        },
                        headers: {
                            'X-CSRF-TOKEN': "{{ csrf_token() }}"
                        },
                        success: function(result) {
                            console.log('Direct delete response:', result);
                            if (result.error === false) {
                                $('#userTable').bootstrapTable('refresh');
                                alert(result.message || "{{ __('User deleted successfully') }}");
                            } else {
                                alert(result.message || "{{ __('Failed to delete user') }}");
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('AJAX error:', status, error);
                            alert("{{ __('An error occurred while deleting the user') }}");
                        }
                    });
                }
            }
        };

        // Global success function for package assignment
        function assignApprovalSuccess() {
            $('#assignPackageModal').modal('hide');
            $('#userTable').bootstrapTable('refresh');
        }

        function resetModal() {
            const modal = $('#assignPackageModal');
            const form = modal.find('form');
            form[0].reset();
        }

        $(document).ready(function() {
            // Initialize the table with events
            $('#userTable').bootstrapTable({
                onPostBody: function() {
                    console.log('Table has been rendered, initializing event handlers');
                    // We initialize handlers after table is rendered
                }
            });
            
            // Add direct event handler for delete button clicks (in addition to the bootstrap table events)
            $(document).on('click', '.delete-user', function(e) {
                // Only handle clicks that aren't already being handled by bootstrap table events
                if (!$(this).closest('tr').attr('data-uniqueid')) {
                    console.log('Direct delete button clicked');
                    const userId = $(this).data('id');
                    console.log('User ID from direct click:', userId);
                    
                    if (confirm("{{ __('Are you sure you want to delete this user?') }}")) {
                        $.ajax({
                            url: "{{ url('customer/delete') }}/" + userId,
                            type: 'DELETE',
                            data: {
                                "_token": "{{ csrf_token() }}"
                            },
                            headers: {
                                'X-CSRF-TOKEN': "{{ csrf_token() }}"
                            },
                            success: function(result) {
                                console.log('Direct delete response:', result);
                                if (result.error === false) {
                                    $('#userTable').bootstrapTable('refresh');
                                    alert(result.message || "{{ __('User deleted successfully') }}");
                                } else {
                                    alert(result.message || "{{ __('Failed to delete user') }}");
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('AJAX error:', status, error);
                                alert("{{ __('An error occurred while deleting the user') }}");
                            }
                        });
                    }
                }
            });
            
            // Explicitly handle the featured-toggle change event
            $(document).on('change', '.featured-toggle', function(e) {
                e.stopPropagation(); // Prevent event bubbling
                const userId = $(this).data('id');
                const isFeatured = $(this).is(':checked') ? 1 : 0;
                const toggleElement = $(this);
                
                console.log('Expert page - Toggle featured for user ID:', userId, 'to status:', isFeatured);
                
                // Disable the toggle during the request to prevent multiple clicks
                toggleElement.prop('disabled', true);
                
                $.ajax({
                    url: "{{ route('customer.toggle.featured') }}",
                    type: 'POST',
                    data: {
                        "_token": "{{ csrf_token() }}",
                        "user_id": userId,
                        "is_featured": isFeatured
                    },
                    headers: {
                        'X-CSRF-TOKEN': "{{ csrf_token() }}"
                    },
                    success: function(result) {
                        console.log('Expert page - Featured toggle response:', result);
                        
                        // Re-enable the toggle
                        toggleElement.prop('disabled', false);
                        
                        if (result.error === true) {
                            // Show error message and revert toggle state
                            alert(result.message || 'Error updating featured status');
                            toggleElement.prop('checked', !isFeatured);
                        } else {
                            // Get actual status from response, don't assume it matches what we sent
                            const actualStatus = result.is_featured || false;
                            console.log('Expert page - Setting featured status to:', actualStatus);
                            
                            // Set the toggle state to the actual status from the server
                            toggleElement.prop('checked', actualStatus);
                            
                            // Success - refresh table to show updated state
                            setTimeout(function() {
                                $('#userTable').bootstrapTable('refresh');
                            }, 500);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Expert page - Featured toggle error:', error, xhr.responseText);
                        
                        // Re-enable the toggle
                        toggleElement.prop('disabled', false);
                        
                        // Show detailed error message if available
                        let errorMessage = 'An error occurred while updating the featured status';
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response && response.message) {
                                errorMessage = response.message;
                            }
                        } catch (e) {
                            console.error('Error parsing error response:', e);
                        }
                        
                        alert(errorMessage);
                        
                        // Revert the toggle to its previous state
                        toggleElement.prop('checked', !isFeatured);
                    }
                });
            });

            // Handle status switch change
            $(document).on('change', '.status-switch', function() {
                let id = $(this).data('id');
                let status = $(this).prop('checked') ? 1 : 0;

                $.ajax({
                    url: "{{ route('customer.toggle.status') }}",
                    type: 'POST',
                    data: {
                        "_token": "{{ csrf_token() }}",
                        "id": id,
                        "status": status
                    },
                    success: function(result) {
                        if (result.error === false) {
                            $('#userTable').bootstrapTable('refresh');
                        }
                    }
                });
            });

            // Handle assign package button click
            $(document).on('click', '.assign_package', function(e) {
                // Try to get userId from different sources
                const userId = $(this).data('id') ||
                    $(this).closest('tr').find('td[data-index="id"]').text() ||
                    $(this).closest('td').data('id');

                console.log('Setting user_id to:', userId); // Debug log
                $('#user_id').val(userId);
            });

            // Handle package type selection
            $('.package_type').on('change', function() {
                const packageType = $(this).val();

                if (packageType === 'item_listing') {
                    $('#item-listing-package-div').show();
                    $('#advertisement-package-div').hide();
                    $('#advertisement-package').prop('disabled', true);
                    $('#item-listing-package').prop('disabled', false);
                } else if (packageType === 'advertisement') {
                    $('#item-listing-package-div').hide();
                    $('#advertisement-package-div').show();
                    $('#item-listing-package').prop('disabled', true);
                    $('#advertisement-package').prop('disabled', false);
                }

                // Reset package details
                $('#package_details').hide();
                $('.payment').hide();
                $('.cheque').hide();
            });

            // Handle package selection
            $('.package').on('change', function() {
                const selectedOption = $(this).find('option:selected');
                const packageDetails = selectedOption.data('details');

                if (packageDetails) {
                    const currencySymbol = $('#currency-settings').data('symbol');
                    const currencyPosition = $('#currency-settings').data('position');

                    $('#package_name').text(packageDetails.name);

                    const priceFormatted = currencyPosition === 'left' ?
                        currencySymbol + packageDetails.price :
                        packageDetails.price + currencySymbol;

                    const finalPriceFormatted = currencyPosition === 'left' ?
                        currencySymbol + packageDetails.final_price :
                        packageDetails.final_price + currencySymbol;

                    $('#package_price').text(priceFormatted);
                    $('#package_final_price').text(finalPriceFormatted);
                    $('#package_duration').text(packageDetails.duration + ' days');

                    $('#package_details').show();
                    $('.payment').show();
                }
            });

            // Handle payment method selection
            $('.payment_gateway').on('change', function() {
                const paymentMethod = $(this).val();

                if (paymentMethod === 'cheque') {
                    $('.cheque').show();
                } else {
                    $('.cheque').hide();
                }
            });
        });
    </script>
@endsection
