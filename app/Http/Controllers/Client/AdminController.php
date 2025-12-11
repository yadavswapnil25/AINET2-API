<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Traits\Response;
use App\Models\Drf;
use App\Models\Ppf;
use App\Models\User;
use App\Models\Blog;
use App\Models\Banner;
use App\Models\Event;
use App\Models\Partner;
use App\Models\Gallery;
use App\Models\Newsletter;
use App\Models\News;
use App\Models\Highlight;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    use Response;

    /**
     * Admin login
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string|min:6'
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            $credentials = $request->only('email', 'password');

            // Attempt to authenticate admin user
            if (Auth::attempt($credentials)) {
                $user = Auth::user();

                if ((int) $user->role_id !== 1) {
                    Auth::logout();
                    return $this->error('Unauthorized', 403, [
                        'message' => 'Only admin users can access the admin panel'
                    ]);
                }

                $accessGrant = $user->createToken('Admin Token');

                return $this->success('Admin logged in successfully', 200, [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role_id' => $user->role_id,
                    ],
                    'access_token' => $accessGrant->accessToken,
                    'token_type' => 'Bearer'
                ]);
            }

            return $this->error('Invalid credentials', 401, [
                'message' => 'Email or password is incorrect'
            ]);

        } catch (\Throwable $e) {
            return $this->error('Login failed', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Admin logout
     */
    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            
            if ($user) {
                // Revoke all tokens for the user
                $user->tokens()->delete();
                
                return $this->success('Admin logged out successfully', 200, [
                    'message' => 'All tokens revoked successfully'
                ]);
            }

            return $this->error('No authenticated user found', 401, [
                'message' => 'User not authenticated'
            ]);

        } catch (\Throwable $e) {
            return $this->error('Logout failed', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Get admin profile
     */
    public function profile(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return $this->error('User not authenticated', 401, [
                    'message' => 'No authenticated user found'
                ]);
            }

            return $this->success('Profile retrieved successfully', 200, [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role_id' => $user->role_id,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ]
            ]);

        } catch (\Throwable $e) {
            return $this->error('Failed to retrieve profile', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Refresh admin token
     */
    public function refreshToken(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return $this->error('User not authenticated', 401, [
                    'message' => 'No authenticated user found'
                ]);
            }

            // Revoke current token
            $request->user()->token()->revoke();
            
            // Create new token
            $accessGrant = $user->createToken('Admin Token');

            return $this->success('Token refreshed successfully', 200, [
                'access_token' => $accessGrant->accessToken,
                'token_type' => 'Bearer'
            ]);

        } catch (\Throwable $e) {
            return $this->error('Token refresh failed', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Get all DRF records with pagination
     */
    public function getDrfList(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search');
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $conferenceFilter = $request->get('conference_filter', '9th_conference'); // Default to 9th conference
            $paymentStatusFilter = $request->get('payment_status'); // Payment status filter

            $query = Drf::query();

            // Filter by conference attendance (default: 9th_conference, or 'all' for all records)
            if ($conferenceFilter !== 'all') {
                $query->where('conference_attendance', $conferenceFilter);
            }

            // Filter by payment status
            if ($paymentStatusFilter) {
                if ($paymentStatusFilter === 'paid') {
                    // Paid: payment_status is 'paid' or 'success'
                    $query->where(function($q) {
                        $q->where('payment_status', 'paid')
                          ->orWhere('payment_status', 'success');
                    });
                } elseif ($paymentStatusFilter === 'unpaid') {
                    // Unpaid: payment_status is 'unpaid' or 'failed' or null
                    $query->where(function($q) {
                        $q->where('payment_status', 'unpaid')
                          ->orWhere('payment_status', 'failed')
                          ->orWhereNull('payment_status');
                    });
                } elseif ($paymentStatusFilter === 'pending') {
                    // Pending: payment_status is 'pending'
                    $query->where('payment_status', 'pending');
                }
            }

            // Search functionality
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%")
                      ->orWhere('institution', 'LIKE', "%{$search}%")
                      ->orWhere('member', 'LIKE', "%{$search}%");
                });
            }

            // Date range filter on created_at
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');

            if ($startDate && $endDate) {
                $query->whereBetween('created_at', [
                    $startDate . ' 00:00:00',
                    $endDate . ' 23:59:59'
                ]);
            } elseif ($startDate) {
                $query->where('created_at', '>=', $startDate . ' 00:00:00');
            } elseif ($endDate) {
                $query->where('created_at', '<=', $endDate . ' 23:59:59');
            }

            // Sorting
            $query->orderBy($sortBy, $sortOrder);

            $drfs = $query->paginate($perPage);

            return $this->success('DRF records retrieved successfully', 200, [
                'drfs' => $drfs->items(),
                'pagination' => [
                    'current_page' => $drfs->currentPage(),
                    'last_page' => $drfs->lastPage(),
                    'per_page' => $drfs->perPage(),
                    'total' => $drfs->total(),
                    'from' => $drfs->firstItem(),
                    'to' => $drfs->lastItem(),
                ]
            ]);

        } catch (\Throwable $e) {
            return $this->error('Failed to retrieve DRF records', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Get single DRF record
     */
    public function getDrf(Request $request, $id)
    {
        try {
            $drf = Drf::find($id);

            if (!$drf) {
                return $this->error('DRF record not found', 404, [
                    'message' => 'No DRF record found with the given ID'
                ]);
            }

            return $this->success('DRF record retrieved successfully', 200, [
                'drf' => $drf
            ]);

        } catch (\Throwable $e) {
            return $this->error('Failed to retrieve DRF record', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Update DRF record
     */
    public function updateDrf(Request $request, $id)
    {
        try {
            // Prepare data for validation - convert empty strings to null
            $data = $request->all();
            
            // Normalize age field - convert empty strings to null for validation
            // Age can be a string (like "41-50" for age ranges) or a number
            // Always keep as string to match validation rule
            if (isset($data['age'])) {
                if ($data['age'] === '' || $data['age'] === 'null') {
                    $data['age'] = null;
                } else {
                    // Always convert to string to match validation rule
                    $data['age'] = (string)$data['age'];
                }
            }
            
            // Normalize country_code - convert empty strings to null
            if (isset($data['country_code'])) {
                if ($data['country_code'] === '' || $data['country_code'] === 'null') {
                    $data['country_code'] = null;
                } else {
                    $data['country_code'] = (string)$data['country_code'];
                }
            }

            $validator = Validator::make($data, [
                'member' => 'sometimes|nullable|string',
                'name' => 'sometimes|nullable|string',
                'gender' => 'sometimes|nullable|string',
                'age' => 'sometimes|nullable|string', // Changed to string to accept age ranges like "41-50"
                'institution' => 'sometimes|nullable|string',
                'address' => 'sometimes|nullable|string',
                'city' => 'sometimes|nullable|string',
                'pincode' => 'sometimes|nullable|string',
                'state' => 'sometimes|nullable|string',
                'country_code' => 'sometimes|nullable|string',
                'phone_no' => 'sometimes|nullable|string',
                'email' => 'sometimes|nullable|email',
                'areas' => 'sometimes|nullable|string',
                'other' => 'sometimes|nullable|string',
                'areas_of_interest' => 'sometimes|nullable|string',
                'experience' => 'sometimes|nullable|string',
                'conference' => 'sometimes|nullable|string',
                'types' => 'sometimes|nullable|string',
                'conference_attendance' => 'sometimes|nullable|string',
                'you_are_register_as' => 'sometimes|nullable|string',
                'pre_title' => 'sometimes|nullable|string',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            $drf = Drf::find($id);

            if (!$drf) {
                return $this->error('DRF record not found', 404, [
                    'message' => 'No DRF record found with the given ID'
                ]);
            }

            // Prepare update data - only include fields that are present in the request
            $updateData = [];
            $allowedFields = [
                'member', 'name', 'gender', 'age', 'institution', 'address',
                'city', 'pincode', 'state', 'country_code', 'phone_no', 'email',
                'areas', 'other', 'areas_of_interest', 'experience', 'conference', 'types', 'conference_attendance', 'you_are_register_as', 'pre_title'
            ];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    // Age is always kept as string (for ranges like "41-50" or single values like "10")
                    // Already normalized above to ensure it's a string
                    $updateData[$field] = $data[$field];
                }
            }

            $drf->update($updateData);

            return $this->success('DRF record updated successfully', 200, [
                'drf' => $drf->fresh()
            ]);

        } catch (\Throwable $e) {
            return $this->error('Failed to update DRF record', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Delete DRF record
     */
    public function deleteDrf(Request $request, $id)
    {
        try {
            $drf = Drf::find($id);

            if (!$drf) {
                return $this->error('DRF record not found', 404, [
                    'message' => 'No DRF record found with the given ID'
                ]);
            }

            $drf->delete();

            return $this->success('DRF record deleted successfully', 200, [
                'message' => 'DRF record has been permanently deleted'
            ]);

        } catch (\Throwable $e) {
            return $this->error('Failed to delete DRF record', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Bulk delete DRF records
     */
    public function bulkDeleteDrf(Request $request)
    {
        try {
            $incomingIds = $request->input('ids', $request->input('data.ids', []));

            $validator = Validator::make(['ids' => $incomingIds], [
                'ids' => 'required|array|min:1',
                'ids.*' => 'integer'
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            $ids = collect($incomingIds)
                ->map(fn ($id) => is_numeric($id) ? (int) $id : null)
                ->filter(fn ($id) => !is_null($id))
                ->unique()
                ->values();

            $existingIds = Drf::whereIn('id', $ids)->pluck('id');

            $missingIds = $ids->diff($existingIds)->values();

            if ($existingIds->isEmpty()) {
                return $this->success('No matching DRF records found', 200, [
                    'deleted_count' => 0,
                    'deleted_ids' => [],
                    'missing_ids' => $missingIds,
                    'message' => 'No DRF records were deleted because none of the provided IDs exist.'
                ]);
            }

            $deletedCount = Drf::whereIn('id', $existingIds)->delete();

            return $this->success('DRF records deleted successfully', 200, [
                'deleted_count' => $deletedCount,
                'deleted_ids' => $existingIds->values(),
                'missing_ids' => $missingIds,
                'message' => "{$deletedCount} DRF records have been permanently deleted"
            ]);

        } catch (\Throwable $e) {
            return $this->error('Failed to delete DRF records', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Get DRF statistics
     */
    public function getDrfStats(Request $request)
    {
        try {
            $stats = [
                'total_drfs' => Drf::count(),
                'total_members' => Drf::where('member', 'Yes')->count(),
                'total_non_members' => Drf::where('member', 'No')->count(),
                'registrations_by_month' => Drf::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
                    ->groupBy('month')
                    ->orderBy('month')
                    ->get(),
                'registrations_by_conference' => Drf::selectRaw('conference, COUNT(*) as count')
                    ->groupBy('conference')
                    ->get(),
                'registrations_by_registration_type' => Drf::selectRaw('you_are_register_as, COUNT(*) as count')
                    ->groupBy('you_are_register_as')
                    ->get(),
            ];

            return $this->success('DRF statistics retrieved successfully', 200, [
                'stats' => $stats
            ]);

        } catch (\Throwable $e) {
            return $this->error('Failed to retrieve DRF statistics', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Export DRF records as CSV
     */
    public function exportDrf(Request $request)
    {
        try {
            $search = $request->get('search');
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            $conferenceFilter = $request->get('conference_filter', '9th_conference'); // Default to 9th conference
            $paymentStatusFilter = $request->get('payment_status'); // Payment status filter
            $selectedIds = $request->get('selected_ids'); // Array of selected IDs to export

            $query = Drf::query();

            // If specific IDs are selected, export only those records (ignore other filters)
            if ($selectedIds && is_array($selectedIds) && count($selectedIds) > 0) {
                $ids = array_filter(array_map('intval', $selectedIds));
                if (count($ids) > 0) {
                    $query->whereIn('id', $ids);
                }
            } else {
                // Apply filters only when no specific IDs are selected
                // Filter by conference attendance (default: 9th_conference, or 'all' for all records)
                if ($conferenceFilter !== 'all') {
                    $query->where('conference_attendance', $conferenceFilter);
                }

                // Filter by payment status
                if ($paymentStatusFilter) {
                    if ($paymentStatusFilter === 'paid') {
                        // Paid: payment_status is 'paid' or 'success'
                        $query->where(function($q) {
                            $q->where('payment_status', 'paid')
                              ->orWhere('payment_status', 'success');
                        });
                    } elseif ($paymentStatusFilter === 'unpaid') {
                        // Unpaid: payment_status is 'unpaid' or 'failed' or null
                        $query->where(function($q) {
                            $q->where('payment_status', 'unpaid')
                              ->orWhere('payment_status', 'failed')
                              ->orWhereNull('payment_status');
                        });
                    } elseif ($paymentStatusFilter === 'pending') {
                        // Pending: payment_status is 'pending'
                        $query->where('payment_status', 'pending');
                    }
                }

                if ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'LIKE', "%{$search}%")
                          ->orWhere('email', 'LIKE', "%{$search}%")
                          ->orWhere('institution', 'LIKE', "%{$search}%")
                          ->orWhere('member', 'LIKE', "%{$search}%");
                    });
                }

                if ($startDate && $endDate) {
                    $query->whereBetween('created_at', [
                        $startDate . ' 00:00:00',
                        $endDate . ' 23:59:59'
                    ]);
                } elseif ($startDate) {
                    $query->where('created_at', '>=', $startDate . ' 00:00:00');
                } elseif ($endDate) {
                    $query->where('created_at', '<=', $endDate . ' 23:59:59');
                }
            }

            $query->orderBy($sortBy, $sortOrder);

            $filename = 'drf_export_' . now()->format('Ymd_His') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'no-store, no-cache',
            ];

            $columns = [
                'id', 'member', 'pre_title', 'name', 'gender', 'age', 'institution', 'address', 'city', 'pincode',
                'state', 'country_code', 'phone_no', 'email', 'areas', 'experience', 'conference', 'you_are_register_as', 'created_at'
            ];

            $callback = function () use ($query, $columns) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, $columns);
                $query->chunk(1000, function ($rows) use ($handle, $columns) {
                    foreach ($rows as $row) {
                        $data = [];
                        foreach ($columns as $col) {
                            $data[] = $row->{$col};
                        }
                        fputcsv($handle, $data);
                    }
                });
                fclose($handle);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Throwable $e) {
            return $this->error('Failed to export DRF records', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Get all PPF records with pagination
     */
    public function getPpfList(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search');
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');

            $query = Ppf::query();

            // Search functionality
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('main_name', 'LIKE', "%{$search}%")
                      ->orWhere('main_email', 'LIKE', "%{$search}%")
                      ->orWhere('main_work', 'LIKE', "%{$search}%")
                      ->orWhere('pr_title', 'LIKE', "%{$search}%")
                      ->orWhere('sub_theme', 'LIKE', "%{$search}%");
                });
            }

            // Date range filter on created_at
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');

            if ($startDate && $endDate) {
                $query->whereBetween('created_at', [
                    $startDate . ' 00:00:00',
                    $endDate . ' 23:59:59'
                ]);
            } elseif ($startDate) {
                $query->where('created_at', '>=', $startDate . ' 00:00:00');
            } elseif ($endDate) {
                $query->where('created_at', '<=', $endDate . ' 23:59:59');
            }

            // Sorting
            $query->orderBy($sortBy, $sortOrder);

            $ppfs = $query->paginate($perPage);

            return $this->success('PPF records retrieved successfully', 200, [
                'ppfs' => $ppfs->items(),
                'pagination' => [
                    'current_page' => $ppfs->currentPage(),
                    'last_page' => $ppfs->lastPage(),
                    'per_page' => $ppfs->perPage(),
                    'total' => $ppfs->total(),
                    'from' => $ppfs->firstItem(),
                    'to' => $ppfs->lastItem(),
                ]
            ]);

        } catch (\Throwable $e) {
            return $this->error('Failed to retrieve PPF records', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Get single PPF record
     */
    public function getPpf(Request $request, $id)
    {
        try {
            $ppf = Ppf::find($id);

            if (!$ppf) {
                return $this->error('PPF record not found', 404, [
                    'message' => 'No PPF record found with the given ID'
                ]);
            }

            return $this->success('PPF record retrieved successfully', 200, [
                'ppf' => $ppf
            ]);

        } catch (\Throwable $e) {
            return $this->error('Failed to retrieve PPF record', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Update PPF record
     */
    public function updatePpf(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'main_title' => 'sometimes|string',
                'main_name' => 'sometimes|string',
                'main_work' => 'sometimes|string',
                'main_phone' => 'sometimes|string',
                'main_country_code' => 'sometimes|string',
                'main_email' => 'sometimes|email',
                'co1_title' => 'sometimes|string',
                'co1_name' => 'sometimes|string',
                'co1_work' => 'sometimes|string',
                'co1_country_code' => 'sometimes|string',
                'co1_phone' => 'sometimes|string',
                'co1_email' => 'sometimes|email',
                'co2_title' => 'sometimes|string',
                'co2_name' => 'sometimes|string',
                'co2_work' => 'sometimes|string',
                'co2_country_code' => 'sometimes|string',
                'co2_phone' => 'sometimes|string',
                'co2_email' => 'sometimes|email',
                'co3_title' => 'sometimes|string',
                'co3_name' => 'sometimes|string',
                'co3_work' => 'sometimes|string',
                'co3_country_code' => 'sometimes|string',
                'co3_phone' => 'sometimes|string',
                'co3_email' => 'sometimes|email',
                'sub_theme' => 'sometimes|string',
                'sub_theme_other' => 'sometimes|string',
                'pr_nature' => 'sometimes|string',
                'pr_title' => 'sometimes|string',
                'pr_abstract' => 'sometimes|string',
                'pr1_bio' => 'sometimes|string',
                'pr2_bio' => 'sometimes|string',
                'pr3_bio' => 'sometimes|string',
                'pr4_bio' => 'sometimes|string',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            $ppf = Ppf::find($id);

            if (!$ppf) {
                return $this->error('PPF record not found', 404, [
                    'message' => 'No PPF record found with the given ID'
                ]);
            }

            $ppf->update($request->only([
                'main_title', 'main_name', 'main_work', 'main_phone', 'main_country_code', 'main_email',
                'co1_title', 'co1_name', 'co1_work', 'co1_country_code', 'co1_phone', 'co1_email',
                'co2_title', 'co2_name', 'co2_work', 'co2_country_code', 'co2_phone', 'co2_email',
                'co3_title', 'co3_name', 'co3_work', 'co3_country_code', 'co3_phone', 'co3_email',
                'sub_theme', 'sub_theme_other', 'pr_nature', 'pr_title', 'pr_abstract',
                'pr1_bio', 'pr2_bio', 'pr3_bio', 'pr4_bio'
            ]));

            return $this->success('PPF record updated successfully', 200, [
                'ppf' => $ppf->fresh()
            ]);

        } catch (\Throwable $e) {
            return $this->error('Failed to update PPF record', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Delete PPF record
     */
    public function deletePpf(Request $request, $id)
    {
        try {
            $ppf = Ppf::find($id);

            if (!$ppf) {
                return $this->error('PPF record not found', 404, [
                    'message' => 'No PPF record found with the given ID'
                ]);
            }

            $ppf->delete();

            return $this->success('PPF record deleted successfully', 200, [
                'message' => 'PPF record has been permanently deleted'
            ]);

        } catch (\Throwable $e) {
            return $this->error('Failed to delete PPF record', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Bulk delete PPF records
     */
    public function bulkDeletePpf(Request $request)
    {
        try {
            $incomingIds = $request->input('ids', $request->input('data.ids', []));

            $validator = Validator::make(['ids' => $incomingIds], [
                'ids' => 'required|array|min:1',
                'ids.*' => 'integer'
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            $ids = collect($incomingIds)
                ->map(function ($id) {
                    return is_numeric($id) ? (int) $id : null;
                })
                ->filter(fn ($id) => !is_null($id))
                ->unique()
                ->values();

            \Log::info('bulkDeletePpf request ids', ['raw_ids' => $incomingIds, 'normalized' => $ids->toArray()]);

            $existingIds = Ppf::whereIn('id', $ids)->pluck('id');
            \Log::info('bulkDeletePpf existing ids', ['found_ids' => $existingIds->toArray()]);

            $missingIds = $ids->diff($existingIds)->values();

            if ($existingIds->isEmpty()) {
                return $this->success('All PPF records have been deleted', 200, [
                    'deleted_ids' => $ids->toArray(),
                    'missing_ids' => $missingIds->toArray()
                ]);
            }

            $deletedCount = Ppf::whereIn('id', $ids)->delete();

            return $this->success('PPF records deleted successfully', 200, [
                'deleted_count' => $deletedCount,
                'message' => "{$deletedCount} PPF records have been permanently deleted"
            ]);

        } catch (\Throwable $e) {
            return $this->error('Failed to delete PPF records', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Get PPF statistics
     */
    public function getPpfStats(Request $request)
    {
        try {
            $stats = [
                'total_ppfs' => Ppf::count(),
                'submissions_by_month' => Ppf::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
                    ->groupBy('month')
                    ->orderBy('month')
                    ->get(),
                'submissions_by_theme' => Ppf::selectRaw('sub_theme, COUNT(*) as count')
                    ->groupBy('sub_theme')
                    ->get(),
                'submissions_by_nature' => Ppf::selectRaw('pr_nature, COUNT(*) as count')
                    ->groupBy('pr_nature')
                    ->get(),
                'co_presenters_breakdown' => [
                    'with_co1' => Ppf::whereNotNull('co1_name')->where('co1_name', '!=', '')->count(),
                    'with_co2' => Ppf::whereNotNull('co2_name')->where('co2_name', '!=', '')->count(),
                    'with_co3' => Ppf::whereNotNull('co3_name')->where('co3_name', '!=', '')->count(),
                    'solo_presentations' => Ppf::where(function($q) {
                        $q->whereNull('co1_name')->orWhere('co1_name', '=', '');
                    })->where(function($q) {
                        $q->whereNull('co2_name')->orWhere('co2_name', '=', '');
                    })->where(function($q) {
                        $q->whereNull('co3_name')->orWhere('co3_name', '=', '');
                    })->count(),
                ]
            ];

            return $this->success('PPF statistics retrieved successfully', 200, [
                'stats' => $stats
            ]);

        } catch (\Throwable $e) {
            return $this->error('Failed to retrieve PPF statistics', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Export PPF records as CSV
     */
    public function exportPpf(Request $request)
    {
        try {
            $search = $request->get('search');
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');

            $query = Ppf::query();

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('main_name', 'LIKE', "%{$search}%")
                      ->orWhere('main_email', 'LIKE', "%{$search}%")
                      ->orWhere('main_work', 'LIKE', "%{$search}%")
                      ->orWhere('pr_title', 'LIKE', "%{$search}%")
                      ->orWhere('sub_theme', 'LIKE', "%{$search}%");
                });
            }

            if ($startDate && $endDate) {
                $query->whereBetween('created_at', [
                    $startDate . ' 00:00:00',
                    $endDate . ' 23:59:59'
                ]);
            } elseif ($startDate) {
                $query->where('created_at', '>=', $startDate . ' 00:00:00');
            } elseif ($endDate) {
                $query->where('created_at', '<=', $endDate . ' 23:59:59');
            }

            $query->orderBy($sortBy, $sortOrder);

            $filename = 'ppf_export_' . now()->format('Ymd_His') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'no-store, no-cache',
            ];

            $columns = [
                'id',
                'main_title', 'main_name', 'main_work', 'main_country_code', 'main_phone', 'main_email',
                'co1_title', 'co1_name', 'co1_work', 'co1_country_code', 'co1_phone', 'co1_email',
                'co2_title', 'co2_name', 'co2_work', 'co2_country_code', 'co2_phone', 'co2_email',
                'co3_title', 'co3_name', 'co3_work', 'co3_country_code', 'co3_phone', 'co3_email',
                'sub_theme', 'sub_theme_other', 'pr_nature', 'pr_title', 'pr_abstract',
                'pr1_bio', 'pr2_bio', 'pr3_bio', 'pr4_bio',
                'created_at'
            ];

            $callback = function () use ($query, $columns) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, $columns);
                $query->chunk(1000, function ($rows) use ($handle, $columns) {
                    foreach ($rows as $row) {
                        $data = [];
                        foreach ($columns as $col) {
                            $data[] = $row->{$col};
                        }
                        fputcsv($handle, $data);
                    }
                });
                fclose($handle);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Throwable $e) {
            return $this->error('Failed to export PPF records', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Get all users with pagination
     */
    public function getUserList(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search');
            $deleted = $request->get('deleted', 'without'); // 'with', 'without', 'only'
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');

            $query = User::query();

            // Handle deleted filter
            if ($deleted === 'only') {
                // Show only deleted users
                $query->onlyTrashed();
            } elseif ($deleted === 'with') {
                // Show all users including deleted
                $query->withTrashed();
            } else {
                // Default: Show only non-deleted users (without)
                $query->whereNull('deleted_at');
            }

            // Search functionality
            if ($search) {
                static $institutionColumns = null;

                if ($institutionColumns === null) {
                    $optionalColumns = [
                        'institution',
                        'name_institution',
                        'address_institution',
                        'type_institution',
                        'other_institution',
                    ];

                    $institutionColumns = array_values(array_filter($optionalColumns, static function (string $column) {
                        return Schema::hasColumn('users', $column);
                    }));
                }

                $searchableColumns = array_merge([
                    'name',
                    'email',
                    'mobile',
                    'm_id',
                ], $institutionColumns);

                $query->where(function ($q) use ($search, $searchableColumns) {
                    foreach ($searchableColumns as $index => $column) {
                        $method = $index === 0 ? 'where' : 'orWhere';
                        $q->{$method}($column, 'LIKE', "%{$search}%");
                    }
                });
            }

            // Sorting
            $query->orderBy($sortBy, $sortOrder);

            $users = $query->paginate($perPage);

            return $this->success('Users retrieved successfully', 200, [
                'users' => $users->items(),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                    'from' => $users->firstItem(),
                    'to' => $users->lastItem(),
                ]
            ]);

        } catch (\Throwable $e) {
            return $this->error('Failed to retrieve users', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Get single user
     */
    public function getUser(Request $request, $id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return $this->error('User not found', 404, [
                    'message' => 'No user found with the given ID'
                ]);
            }

            return $this->success('User retrieved successfully', 200, [
                'user' => $user
            ]);

        } catch (\Throwable $e) {
            return $this->error('Failed to retrieve user', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Generate login token for a user (Admin login as user)
     */
    public function loginAsUser(Request $request, $id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return $this->error('User not found', 404, [
                    'message' => 'No user found with the given ID'
                ]);
            }

            // Check if user is soft deleted
            if ($user->deleted_at) {
                return $this->error('Cannot login as deleted user', 400, [
                    'message' => 'The user has been deleted and cannot be logged in'
                ]);
            }

            // Generate token for the user (similar to LoginController)
            $fingerPrint = $request->fingerprint() ?? 'Admin Login As User';
            $accessGrant = $user->createToken($fingerPrint);

            return $this->success('Login token generated successfully', 200, [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'token' => $accessGrant->accessToken
            ]);

        } catch (\Throwable $e) {
            return $this->error('Failed to generate login token', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Create new user
     */
    public function createUser(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8',
                'mobile' => 'nullable|string|max:20',
                'gender' => 'nullable|string',
                'm_id' => 'nullable|string|unique:users,m_id',
                'first_name' => 'nullable|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'dob' => 'nullable|date',
                'whatsapp_no' => 'nullable|string|max:20',
                'address' => 'nullable|string',
                'state' => 'nullable|string',
                'district' => 'nullable|string',
                'teaching_exp' => 'nullable|integer',
                'qualification' => 'nullable|json',
                'area_of_work' => 'nullable|json',
                'membership_type' => 'nullable|string',
                'membership_plan' => 'nullable|string',
                'pin' => 'nullable|string',
                'title' => 'nullable|string',
                'address_institution' => 'nullable|string',
                'name_institution' => 'nullable|string',
                'type_institution' => 'nullable|string',
                'other_institution' => 'nullable|string',
                'contact_person' => 'nullable|string',
                'role_id' => 'required|integer|exists:roles,id',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            $userData = $request->all();
            $userData['password'] = Hash::make($request->password);

            $user = User::create($userData);

            return $this->success('User created successfully', 201, [
                'user' => $user
            ]);

        } catch (\Throwable $e) {
            return $this->error('Failed to create user', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Update user
     */
    public function updateUser(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $id,
                'password' => 'sometimes|string|min:8',
                'mobile' => 'nullable|string|max:20',
                'gender' => 'nullable|string',
                'm_id' => 'nullable|string|unique:users,m_id,' . $id,
                'first_name' => 'nullable|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'dob' => 'nullable|date',
                'whatsapp_no' => 'nullable|string|max:20',
                'address' => 'nullable|string',
                'state' => 'nullable|string',
                'district' => 'nullable|string',
                'teaching_exp' => 'nullable|integer',
                'qualification' => 'nullable|json',
                'area_of_work' => 'nullable|json',
                'membership_type' => 'nullable|string',
                'membership_plan' => 'nullable|string',
                'pin' => 'nullable|string',
                'title' => 'nullable|string',
                'address_institution' => 'nullable|string',
                'name_institution' => 'nullable|string',
                'type_institution' => 'nullable|string',
                'other_institution' => 'nullable|string',
                'contact_person' => 'nullable|string',
                'role_id' => 'required|integer|exists:roles,id',
                'addMonths' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            $user = User::find($id);

            if (!$user) {
                return $this->error('User not found', 404, [
                    'message' => 'No user found with the given ID'
                ]);
            }

            $userData = $request->all();
            
            // Hash password if provided
            if ($request->has('password')) {
                $userData['password'] = Hash::make($request->password);
            }

            $user->update($userData);

            return $this->success('User updated successfully', 200, [
                'user' => $user->fresh()
            ]);

        } catch (\Throwable $e) {
            return $this->error('Failed to update user', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Delete user
     */
    public function deleteUser(Request $request, $id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return $this->error('User not found', 404, [
                    'message' => 'No user found with the given ID'
                ]);
            }

            // Prevent deleting the current admin user
            if ($user->id === $request->user()->id) {
                return $this->error('Cannot delete your own account', 400, [
                    'message' => 'You cannot delete your own user account'
                ]);
            }

            // Soft delete
            $user->delete();

            return $this->success('User deleted successfully', 200, [
                'message' => 'User has been deleted'
            ]);

        } catch (\Throwable $e) {
            return $this->error('Failed to delete user', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Permanently delete (hard delete) a user from database
     */
    public function forceDeleteUser(Request $request, $id)
    {
        try {
            // Find user including trashed
            $user = User::withTrashed()->find($id);

            if (!$user) {
                return $this->error('User not found', 404, [
                    'message' => 'No user found with the given ID'
                ]);
            }

            // Prevent deleting the current admin user
            if ($user->id === $request->user()->id) {
                return $this->error('Cannot delete your own account', 400, [
                    'message' => 'You cannot permanently delete your own user account'
                ]);
            }

            // Hard delete (permanently remove from database)
            $user->forceDelete();

            return $this->success('User permanently deleted successfully', 200, [
                'message' => 'User has been permanently deleted from the database'
            ]);

        } catch (\Throwable $e) {
            return $this->error('Failed to permanently delete user', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Restore a soft-deleted user
     */
    public function restoreUser(Request $request, $id)
    {
        try {
            // Find user including trashed
            $user = User::withTrashed()->find($id);

            if (!$user) {
                return $this->error('User not found', 404, [
                    'message' => 'No user found with the given ID'
                ]);
            }

            if (!$user->trashed()) {
                return $this->error('User is not deleted', 400, [
                    'message' => 'This user is not deleted and cannot be restored'
                ]);
            }

            // Restore the user
            $user->restore();

            return $this->success('User restored successfully', 200, [
                'message' => 'User has been restored successfully',
                'user' => $user->fresh()
            ]);

        } catch (\Throwable $e) {
            return $this->error('Failed to restore user', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Bulk delete users
     */
    public function bulkDeleteUser(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array|min:1',
                'ids.*' => 'integer|exists:users,id'
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            // Prevent deleting the current admin user
            $currentUserId = $request->user()->id;
            if (in_array($currentUserId, $request->ids)) {
                return $this->error('Cannot delete your own account', 400, [
                    'message' => 'You cannot delete your own user account'
                ]);
            }

            $deletedCount = User::whereIn('id', $request->ids)->delete();

            return $this->success('Users deleted successfully', 200, [
                'deleted_count' => $deletedCount,
                'message' => "{$deletedCount} users have been permanently deleted"
            ]);

        } catch (\Throwable $e) {
            return $this->error('Failed to delete users', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Get user statistics
     */
    public function getUserStats(Request $request)
    {
        try {
            // Get all users with membership ID
            $membersWithId = User::whereNotNull('m_id')
                ->where(function($q) {
                    $q->where('role_id', '!=', 1)->orWhereNull('role_id');
                })
                ->get();

            // Calculate active and inactive members based on expiry
            $activeMembers = 0;
            $inactiveMembers = 0;
            $now = now();

            foreach ($membersWithId as $user) {
                // Calculate expiry date: member_date + addMonths
                // Use member_date if available, otherwise fallback to created_at
                $memberDate = $user->member_date ?? $user->created_at;
                $addMonths = $user->addMonths ?? 12; // Default to 12 months if not set
                
                // Calculate expiry date: add months and set to last day of that month with original time
                $expiryDate = $memberDate->copy()->addMonths($addMonths);
                // Get the last day of the expiry month
                $lastDayOfMonth = $expiryDate->copy()->endOfMonth()->day;
                // Set to last day of month but keep original time
                $expiryDate = $expiryDate->setDate($expiryDate->year, $expiryDate->month, $lastDayOfMonth)
                    ->setTime($memberDate->hour, $memberDate->minute, $memberDate->second);
                
                // Check if membership is still valid
                if ($now->lessThanOrEqualTo($expiryDate)) {
                    $activeMembers++;
                } else {
                    $inactiveMembers++;
                }
            }

            $stats = [
                'total_users' => User::count(),
                'users_by_month' => User::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
                    ->groupBy('month')
                    ->orderBy('month')
                    ->get(),
                'users_by_gender' => User::selectRaw('gender, COUNT(*) as count')
                    ->whereNotNull('gender')
                    ->groupBy('gender')
                    ->get(),
                'users_by_membership_type' => User::selectRaw('membership_type, COUNT(*) as count')
                    ->whereNotNull('membership_type')
                    ->groupBy('membership_type')
                    ->get(),
                'users_by_state' => User::selectRaw('state, COUNT(*) as count')
                    ->whereNotNull('state')
                    ->groupBy('state')
                    ->orderBy('count', 'desc')
                    ->limit(10)
                    ->get(),
                'users_with_membership_id' => User::whereNotNull('m_id')->count(),
                'users_without_membership_id' => User::whereNull('m_id')->count(),
                'male_members' => User::where('gender', 'Male')->count(),
                'female_members' => User::where('gender', 'Female')->count(),
                'blocked_members' => User::where('status', 0)->count(),
                'active_members' => $activeMembers,
                'inactive_members' => $inactiveMembers,
            ];

            return $this->success('User statistics retrieved successfully', 200, [
                'stats' => $stats
            ]);

        } catch (\Throwable $e) {
            return $this->error('Failed to retrieve user statistics', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Get all blogs with pagination
     */
    public function getBlogList(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search');
            $status = $request->get('status');
            $category = $request->get('category');
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');

            $query = Blog::with('author');

            // Search functionality
            if ($search) {
                $query->search($search);
            }

            // Filter by status
            if ($status) {
                $query->where('status', $status);
            }

            // Filter by category
            if ($category) {
                $query->byCategory($category);
            }

            // Sorting
            $query->orderBy($sortBy, $sortOrder);

            $blogs = $query->paginate($perPage);

            return $this->success('Blogs retrieved successfully', 200, [
                'blogs' => $blogs->items(),
                'pagination' => [
                    'current_page' => $blogs->currentPage(),
                    'last_page' => $blogs->lastPage(),
                    'per_page' => $blogs->perPage(),
                    'total' => $blogs->total(),
                    'from' => $blogs->firstItem(),
                    'to' => $blogs->lastItem(),
                ]
            ]);

        } catch (\Throwable $e) {
            return $this->error('Failed to retrieve blogs', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Get single blog
     */
    public function getBlog(Request $request, $id)
    {
        try {
            $blog = Blog::with('author')->find($id);

            if (!$blog) {
                return $this->error('Blog not found', 404, [
                    'message' => 'No blog found with the given ID'
                ]);
            }

            // Increment view count if blog is published
            if ($blog->status === 'published') {
                $blog->increment('views_count');
            }

            return $this->success('Blog retrieved successfully', 200, [
                'blog' => $blog
            ]);

        } catch (\Throwable $e) {
            return $this->error('Failed to retrieve blog', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Create new blog
     */
    public function createBlog(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'excerpt' => 'nullable|string|max:500',
                'featured_image' => 'nullable|string',
                'status' => 'required|in:draft,published,scheduled',
                'author_id' => 'required|exists:users,id',
                'published_at' => 'nullable|date',
                'meta_title' => 'nullable|string|max:255',
                'meta_description' => 'nullable|string|max:500',
                'tags' => 'nullable|array',
                'tags.*' => 'string',
                'category' => 'nullable|string|max:100',
                'is_featured' => 'nullable|boolean',
                'sort_order' => 'nullable|integer|min:0',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            $blogData = $request->all();
            
            // Generate slug from title
            $blogData['slug'] = Str::slug($request->title);
            
            // Ensure unique slug
            $originalSlug = $blogData['slug'];
            $counter = 1;
            while (Blog::where('slug', $blogData['slug'])->exists()) {
                $blogData['slug'] = $originalSlug . '-' . $counter;
                $counter++;
            }

            // Set published_at if status is published and no date provided
            if ($request->status === 'published' && !$request->published_at) {
                $blogData['published_at'] = now();
            }

            $blog = Blog::create($blogData);

            return $this->success('Blog created successfully', 201, [
                'blog' => $blog->load('author')
            ]);

        } catch (\Throwable $e) {
            return $this->error('Failed to create blog', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Update blog
     */
    public function updateBlog(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|string|max:255',
                'content' => 'sometimes|string',
                'excerpt' => 'nullable|string|max:500',
                'featured_image' => 'nullable|string',
                'status' => 'sometimes|in:draft,published,scheduled',
                'author_id' => 'sometimes|exists:users,id',
                'published_at' => 'nullable|date',
                'meta_title' => 'nullable|string|max:255',
                'meta_description' => 'nullable|string|max:500',
                'tags' => 'nullable|array',
                'tags.*' => 'string',
                'category' => 'nullable|string|max:100',
                'is_featured' => 'nullable|boolean',
                'sort_order' => 'nullable|integer|min:0',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            $blog = Blog::find($id);

            if (!$blog) {
                return $this->error('Blog not found', 404, [
                    'message' => 'No blog found with the given ID'
                ]);
            }

            $blogData = $request->all();

            // Generate new slug if title is being updated
            if ($request->has('title') && $request->title !== $blog->title) {
                $blogData['slug'] = Str::slug($request->title);
                
                // Ensure unique slug
                $originalSlug = $blogData['slug'];
                $counter = 1;
                while (Blog::where('slug', $blogData['slug'])->where('id', '!=', $id)->exists()) {
                    $blogData['slug'] = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }

            // Set published_at if status is being changed to published
            if ($request->has('status') && $request->status === 'published' && $blog->status !== 'published') {
                if (!$request->published_at) {
                    $blogData['published_at'] = now();
                }
            }

            $blog->update($blogData);

            return $this->success('Blog updated successfully', 200, [
                'blog' => $blog->fresh()->load('author')
            ]);

        } catch (\Throwable $e) {
            return $this->error('Failed to update blog', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Delete blog
     */
    public function deleteBlog(Request $request, $id)
    {
        try {
            $blog = Blog::find($id);

            if (!$blog) {
                return $this->error('Blog not found', 404, [
                    'message' => 'No blog found with the given ID'
                ]);
            }

            $blog->delete();

            return $this->success('Blog deleted successfully', 200, [
                'message' => 'Blog has been permanently deleted'
            ]);

        } catch (\Throwable $e) {
            return $this->error('Failed to delete blog', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Bulk delete blogs
     */
    public function bulkDeleteBlog(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array|min:1',
                'ids.*' => 'integer|exists:blogs,id'
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            $deletedCount = Blog::whereIn('id', $request->ids)->delete();

            return $this->success('Blogs deleted successfully', 200, [
                'deleted_count' => $deletedCount,
                'message' => "{$deletedCount} blogs have been permanently deleted"
            ]);

        } catch (\Throwable $e) {
            return $this->error('Failed to delete blogs', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Get blog statistics
     */
    public function getBlogStats(Request $request)
    {
        try {
            $stats = [
                'total_blogs' => Blog::count(),
                'published_blogs' => Blog::published()->count(),
                'draft_blogs' => Blog::where('status', 'draft')->count(),
                'scheduled_blogs' => Blog::where('status', 'scheduled')->count(),
                'featured_blogs' => Blog::featured()->count(),
                'total_views' => Blog::sum('views_count'),
                'blogs_by_month' => Blog::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
                    ->groupBy('month')
                    ->orderBy('month')
                    ->get(),
                'blogs_by_category' => Blog::selectRaw('category, COUNT(*) as count')
                    ->whereNotNull('category')
                    ->groupBy('category')
                    ->orderBy('count', 'desc')
                    ->get(),
                'blogs_by_author' => Blog::selectRaw('author_id, COUNT(*) as count')
                    ->with('author:id,name')
                    ->groupBy('author_id')
                    ->orderBy('count', 'desc')
                    ->limit(10)
                    ->get(),
                'most_viewed_blogs' => Blog::published()
                    ->orderBy('views_count', 'desc')
                    ->limit(10)
                    ->get(['id', 'title', 'views_count', 'slug']),
            ];

            return $this->success('Blog statistics retrieved successfully', 200, [
                'stats' => $stats
            ]);

        } catch (\Throwable $e) {
            return $this->error('Failed to retrieve blog statistics', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Banner Management
     */
    public function getBannerList(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search');
            $sortBy = $request->get('sort_by', 'sort_order');
            $sortOrder = $request->get('sort_order', 'asc');
            $isActive = $request->get('is_active');

            $query = Banner::query();

            if ($search) {
                $query->where('title', 'LIKE', "%{$search}%");
            }

            if ($isActive !== null) {
                $query->where('is_active', filter_var($isActive, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE));
            }

            $query->orderBy($sortBy, $sortOrder);

            $banners = $query->paginate($perPage);

            // Map with image_url for convenience
            $items = collect($banners->items())->map(function ($b) {
                $b->image_url = $b->image_path ? url($b->image_path) : null;
                return $b;
            });

            return $this->success('Banners retrieved successfully', 200, [
                'banners' => $items,
                'pagination' => [
                    'current_page' => $banners->currentPage(),
                    'last_page' => $banners->lastPage(),
                    'per_page' => $banners->perPage(),
                    'total' => $banners->total(),
                    'from' => $banners->firstItem(),
                    'to' => $banners->lastItem(),
                ]
            ]);
        } catch (\Throwable $e) {
            return $this->error('Failed to retrieve banners', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    public function getBanner(Request $request, $id)
    {
        try {
            $banner = Banner::find($id);
            if (!$banner) {
                return $this->error('Banner not found', 404, [
                    'message' => 'No banner found with the given ID'
                ]);
            }
            $banner->image_url = $banner->image_path ? url($banner->image_path) : null;
            return $this->success('Banner retrieved successfully', 200, [
                'banner' => $banner
            ]);
        } catch (\Throwable $e) {
            return $this->error('Failed to retrieve banner', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    public function createBanner(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'image' => 'required|image|mimes:jpg,jpeg,png,webp,svg|max:5120',
                'link_url' => 'nullable|url',
                'is_active' => 'nullable|boolean',
                'sort_order' => 'nullable|integer|min:0',
                'starts_at' => 'nullable|date',
                'ends_at' => 'nullable|date|after_or_equal:starts_at',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            $imagePath = null;
            if ($request->hasFile('image')) {
                $stored = $request->file('image')->store('banners', 'public');
                $imagePath = 'storage/' . $stored; // public URL path
            }

            $banner = Banner::create([
                'title' => $request->title,
                'image_path' => $imagePath,
                'link_url' => $request->link_url,
                'is_active' => $request->boolean('is_active', true),
                'sort_order' => $request->input('sort_order', 0),
                'starts_at' => $request->starts_at,
                'ends_at' => $request->ends_at,
            ]);

            $banner->image_url = $banner->image_path ? url($banner->image_path) : null;

            return $this->success('Banner created successfully', 201, [
                'banner' => $banner
            ]);
        } catch (\Throwable $e) {
            return $this->error('Failed to create banner', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    public function updateBanner(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|string|max:255',
                'image' => 'sometimes|image|mimes:jpg,jpeg,png,webp,svg|max:5120',
                'link_url' => 'nullable|url',
                'is_active' => 'nullable|boolean',
                'sort_order' => 'nullable|integer|min:0',
                'starts_at' => 'nullable|date',
                'ends_at' => 'nullable|date|after_or_equal:starts_at',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            $banner = Banner::find($id);
            if (!$banner) {
                return $this->error('Banner not found', 404, [
                    'message' => 'No banner found with the given ID'
                ]);
            }

            $updateData = $request->only(['title', 'link_url', 'is_active', 'sort_order', 'starts_at', 'ends_at']);

            if ($request->hasFile('image')) {
                $stored = $request->file('image')->store('banners', 'public');
                $updateData['image_path'] = 'storage/' . $stored;
            }

            $banner->update($updateData);
            $banner->image_url = $banner->image_path ? url($banner->image_path) : null;

            return $this->success('Banner updated successfully', 200, [
                'banner' => $banner->fresh()
            ]);
        } catch (\Throwable $e) {
            return $this->error('Failed to update banner', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    public function deleteBanner(Request $request, $id)
    {
        try {
            $banner = Banner::find($id);
            if (!$banner) {
                return $this->error('Banner not found', 404, [
                    'message' => 'No banner found with the given ID'
                ]);
            }
            $banner->delete();
            return $this->success('Banner deleted successfully', 200, [
                'message' => 'Banner has been permanently deleted'
            ]);
        } catch (\Throwable $e) {
            return $this->error('Failed to delete banner', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    public function bulkDeleteBanner(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array|min:1',
                'ids.*' => 'integer|exists:banners,id'
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            $deletedCount = Banner::whereIn('id', $request->ids)->delete();

            return $this->success('Banners deleted successfully', 200, [
                'deleted_count' => $deletedCount,
                'message' => "{$deletedCount} banners have been permanently deleted"
            ]);
        } catch (\Throwable $e) {
            return $this->error('Failed to delete banners', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Get users with role_id = 1 (Admin users)
     */
    public function getAdminUsers(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search');
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');

            $query = User::where('role_id', 1);

            // Search functionality
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%")
                      ->orWhere('mobile', 'LIKE', "%{$search}%");
                });
            }

            // Sorting
            $query->orderBy($sortBy, $sortOrder);

            $users = $query->paginate($perPage);

            return $this->success('Admin users retrieved successfully', 200, [
                'users' => $users->items(),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                    'from' => $users->firstItem(),
                    'to' => $users->lastItem(),
                ]
            ]);

        } catch (\Throwable $e) {
            return $this->error('Failed to retrieve admin users', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Get users with role_id = 1 (Admin users) - Simple list
     */
    public function getAdminUsersList(Request $request)
    {
        try {
            $users = User::where('role_id', 1)
                ->select('id', 'name', 'email', 'mobile', 'created_at')
                ->orderBy('name', 'asc')
                ->get();

            return $this->success('Admin users list retrieved successfully', 200, [
                'admin_users' => $users,
                'total_count' => $users->count()
            ]);

        } catch (\Throwable $e) {
            return $this->error('Failed to retrieve admin users list', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Get all membership records with pagination, search, and filters
     */
    public function getMembershipList(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search');
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $membershipType = $request->get('membership_type');
            $membershipPlan = $request->get('membership_plan');
            $state = $request->get('state');
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');

            $query = User::query();

            // Only get users with membership data (exclude admin users)
            $query->where(function($q) {
                $q->where('role_id', '!=', 1)->orWhereNull('role_id');
            });

            // Search functionality
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%")
                      ->orWhere('mobile', 'LIKE', "%{$search}%")
                      ->orWhere('m_id', 'LIKE', "%{$search}%")
                      ->orWhere('first_name', 'LIKE', "%{$search}%")
                      ->orWhere('last_name', 'LIKE', "%{$search}%");
                });
            }

            // Filter by membership type
            if ($membershipType) {
                $query->where('membership_type', $membershipType);
            }

            // Filter by membership plan
            if ($membershipPlan) {
                $query->where('membership_plan', $membershipPlan);
            }

            // Filter by state
            if ($state) {
                $query->where('state', $state);
            }

            // Date range filter on created_at
            if ($startDate && $endDate) {
                $query->whereBetween('created_at', [
                    $startDate . ' 00:00:00',
                    $endDate . ' 23:59:59'
                ]);
            } elseif ($startDate) {
                $query->where('created_at', '>=', $startDate . ' 00:00:00');
            } elseif ($endDate) {
                $query->where('created_at', '<=', $endDate . ' 23:59:59');
            }

            // Sorting
            $query->orderBy($sortBy, $sortOrder);

            $memberships = $query->paginate($perPage);

            return $this->success('Membership records retrieved successfully', 200, [
                'memberships' => $memberships->items(),
                'pagination' => [
                    'current_page' => $memberships->currentPage(),
                    'last_page' => $memberships->lastPage(),
                    'per_page' => $memberships->perPage(),
                    'total' => $memberships->total(),
                    'from' => $memberships->firstItem(),
                    'to' => $memberships->lastItem(),
                ]
            ]);

        } catch (\Throwable $e) {
            return $this->error('Failed to retrieve membership records', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Get single membership record by ID
     */
    public function getMembership($id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return $this->error('Membership record not found', 404, [
                    'message' => 'No membership record found with the given ID'
                ]);
            }

            // Prevent viewing admin users through membership management
            if ($user->role_id === 1) {
                return $this->error('Cannot view admin user', 400, [
                    'message' => 'Admin users cannot be viewed through membership management'
                ]);
            }

            return $this->success('Membership record retrieved successfully', 200, [
                'membership' => $user
            ]);

        } catch (\Throwable $e) {
            return $this->error('Failed to retrieve membership record', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Export membership records as CSV
     */
    public function exportMembership(Request $request)
    {
        try {
            $search = $request->get('search');
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $membershipType = $request->get('membership_type');
            $membershipPlan = $request->get('membership_plan');
            $state = $request->get('state');
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');

            $query = User::query();

            // Only get users with membership data (exclude admin users)
            $query->where(function($q) {
                $q->where('role_id', '!=', 1)->orWhereNull('role_id');
            });

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%")
                      ->orWhere('mobile', 'LIKE', "%{$search}%")
                      ->orWhere('m_id', 'LIKE', "%{$search}%")
                      ->orWhere('first_name', 'LIKE', "%{$search}%")
                      ->orWhere('last_name', 'LIKE', "%{$search}%");
                    
                    // Only search in name_institution if the column exists
                    if (Schema::hasColumn('users', 'name_institution')) {
                        $q->orWhere('name_institution', 'LIKE', "%{$search}%");
                    }
                });
            }

            if ($membershipType) {
                $query->where('membership_type', $membershipType);
            }

            if ($membershipPlan) {
                $query->where('membership_plan', $membershipPlan);
            }

            if ($state) {
                $query->where('state', $state);
            }

            if ($startDate && $endDate) {
                $query->whereBetween('created_at', [
                    $startDate . ' 00:00:00',
                    $endDate . ' 23:59:59'
                ]);
            } elseif ($startDate) {
                $query->where('created_at', '>=', $startDate . ' 00:00:00');
            } elseif ($endDate) {
                $query->where('created_at', '<=', $endDate . ' 23:59:59');
            }

            $query->orderBy($sortBy, $sortOrder);

            $filename = 'membership_export_' . now()->format('Ymd_His') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'no-store, no-cache',
            ];

            // Get existing columns in the users table
            $existingColumns = Schema::getColumnListing('users');
            
            // Define all possible columns, but only include those that exist in the database
            $allColumns = [
                'id', 'ref', 'm_id', 'first_name', 'last_name', 'name', 'email', 'mobile', 'whatsapp_no',
                'gender', 'dob', 'title', 'address', 'state', 'district', 'pin',
                'qualification', 'area_of_work', 'teaching_exp',
                'membership_type', 'membership_plan',
                'has_member_any', 'name_association', 'expectation', 'has_newsletter',
                'name_institution', 'address_institution', 'type_institution', 'other_institution', 'contact_person',
                'created_at', 'updated_at'
            ];
            
            // Filter to only include columns that exist in the database
            $columns = array_filter($allColumns, function($col) use ($existingColumns) {
                return in_array($col, $existingColumns);
            });
            
            // Re-index array to maintain sequential keys
            $columns = array_values($columns);

            $callback = function () use ($query, $columns) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, $columns);
                $query->chunk(1000, function ($rows) use ($handle, $columns) {
                    foreach ($rows as $row) {
                        $data = [];
                        foreach ($columns as $col) {
                            if ($col === 'qualification' || $col === 'area_of_work') {
                                $data[] = is_string($row->{$col}) ? $row->{$col} : json_encode($row->{$col});
                            } else {
                                $data[] = $row->{$col} ?? '';
                            }
                        }
                        fputcsv($handle, $data);
                    }
                });
                fclose($handle);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Throwable $e) {
            return $this->error('Failed to export membership records', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Update membership record (User)
     */
    public function updateMembership(Request $request, $id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return $this->error('Membership record not found', 404, [
                    'message' => 'No membership record found with the given ID'
                ]);
            }

            // Prevent updating admin users through membership management
            if ($user->role_id === 1) {
                return $this->error('Cannot update admin user', 400, [
                    'message' => 'Admin users cannot be updated through membership management'
                ]);
            }

            // Prepare data for validation
            $data = $request->all();

            // Normalize date fields
            if (isset($data['member_date']) && ($data['member_date'] === '' || $data['member_date'] === 'null')) {
                $data['member_date'] = null;
            }
            if (isset($data['dob']) && ($data['dob'] === '' || $data['dob'] === 'null')) {
                $data['dob'] = null;
            }

            // Normalize numeric fields
            if (isset($data['addMonths'])) {
                if ($data['addMonths'] === '' || $data['addMonths'] === 'null') {
                    $data['addMonths'] = null;
                } else {
                    $data['addMonths'] = is_numeric($data['addMonths']) ? (int)$data['addMonths'] : null;
                }
            }
            if (isset($data['teaching_exp'])) {
                if ($data['teaching_exp'] === '' || $data['teaching_exp'] === 'null') {
                    $data['teaching_exp'] = null;
                } else {
                    // Convert to string if numeric, otherwise keep as is
                    $data['teaching_exp'] = is_numeric($data['teaching_exp']) ? (string)$data['teaching_exp'] : $data['teaching_exp'];
                }
            }

            // Normalize boolean fields
            if (isset($data['has_member_any'])) {
                $data['has_member_any'] = filter_var($data['has_member_any'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            }
            if (isset($data['has_newsletter'])) {
                $data['has_newsletter'] = filter_var($data['has_newsletter'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            }

            // Normalize JSON fields
            if (isset($data['qualification']) && is_string($data['qualification'])) {
                $decoded = json_decode($data['qualification'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $data['qualification'] = $decoded;
                }
            }
            if (isset($data['area_of_work']) && is_string($data['area_of_work'])) {
                $decoded = json_decode($data['area_of_work'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $data['area_of_work'] = $decoded;
                }
            }

            $validator = Validator::make($data, [
                'ref' => 'sometimes|nullable|string|max:255',
                'm_id' => 'sometimes|nullable|unique:users,m_id,' . $id,
                'first_name' => 'sometimes|nullable|string|max:255',
                'last_name' => 'sometimes|nullable|string|max:255',
                'name' => 'sometimes|nullable|string|max:255',
                'email' => 'sometimes|nullable|email|max:255|unique:users,email,' . $id,
                'mobile' => 'sometimes|nullable|string|max:20',
                'whatsapp_no' => 'sometimes|nullable|string|max:20',
                'gender' => 'sometimes|nullable|string|max:50',
                'dob' => 'sometimes|nullable|date',
                'title' => 'sometimes|nullable|string|max:50',
                'address' => 'sometimes|nullable|string',
                'state' => 'sometimes|nullable|string|max:255',
                'district' => 'sometimes|nullable|string|max:255',
                'pin' => 'sometimes|nullable|string|max:20',
                'teaching_exp' => 'sometimes|nullable|string|max:255',
                'qualification' => 'sometimes|nullable',
                'area_of_work' => 'sometimes|nullable',
                'membership_type' => 'sometimes|nullable|string|max:50',
                'membership_plan' => 'sometimes|nullable|string|max:50',
                'has_member_any' => 'sometimes|nullable|boolean',
                'name_association' => 'sometimes|nullable|string|max:255',
                'expectation' => 'sometimes|nullable|string',
                'has_newsletter' => 'sometimes|nullable|boolean',
                'name_institution' => 'sometimes|nullable|string|max:255',
                'address_institution' => 'sometimes|nullable|string',
                'type_institution' => 'sometimes|nullable|string|max:255',
                'other_institution' => 'sometimes|nullable|string|max:255',
                'contact_person' => 'sometimes|nullable|string|max:255',
                'addMonths' => 'sometimes|nullable|integer|min:1',
                'member_date' => 'sometimes|nullable|date',
                'status' => 'sometimes|nullable|integer|in:0,1',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            // Prepare update data - only include fields that are present in the request and exist in the database
            $updateData = [];
            $allowedFields = [
                'ref', 'm_id', 'first_name', 'last_name', 'name', 'email', 'mobile', 'whatsapp_no',
                'gender', 'dob', 'title', 'address', 'state', 'district', 'pin',
                'teaching_exp', 'qualification', 'area_of_work',
                'membership_type', 'membership_plan',
                'has_member_any', 'name_association', 'expectation', 'has_newsletter',
                'name_institution', 'address_institution', 'type_institution', 'other_institution', 'contact_person',
                'addMonths', 'member_date', 'status'
            ];
            // Get existing columns in the users table
            $existingColumns = Schema::getColumnListing('users');
            
            foreach ($allowedFields as $field) {
                // Use array_key_exists to check if key exists (even if value is null or empty string)
                // Only include field if it exists in the request AND exists in the database table
                if (array_key_exists($field, $data) && in_array($field, $existingColumns)) {
                    // Special handling for m_id - normalize empty strings to null
                    if ($field === 'm_id') {
                        $updateData[$field] = ($data[$field] === '' || $data[$field] === null) ? null : trim((string)$data[$field]);
                    } else {
                        $updateData[$field] = $data[$field];
                    }
                }
            }

            $user->update($updateData);

            return $this->success('Membership record updated successfully', 200, [
                'membership' => $user->fresh()
            ]);

        } catch (\Throwable $e) {
            return $this->error('Failed to update membership record', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Soft delete membership record (User)
     */
    public function deleteMembership(Request $request, $id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return $this->error('Membership record not found', 404, [
                    'message' => 'No membership record found with the given ID'
                ]);
            }

            // Prevent deleting admin users
            if ($user->role_id === 1) {
                return $this->error('Cannot delete admin user', 400, [
                    'message' => 'Admin users cannot be deleted through membership management'
                ]);
            }

            // Prevent deleting the current user
            if ($user->id === $request->user()->id) {
                return $this->error('Cannot delete your own account', 400, [
                    'message' => 'You cannot delete your own user account'
                ]);
            }

            $user->delete(); // Soft delete

            return $this->success('Membership record deleted successfully', 200, [
                'message' => 'Membership record has been moved to trash',
                'id' => $id
            ]);

        } catch (\Throwable $e) {
            return $this->error('Failed to delete membership record', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Bulk delete membership records (soft delete)
     */
    public function bulkDeleteMembership(Request $request)
    {
        try {
            $incomingIds = $request->input('ids', $request->input('data.ids', []));

            $validator = Validator::make(['ids' => $incomingIds], [
                'ids' => 'required|array|min:1',
                'ids.*' => 'integer'
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            $ids = collect($incomingIds)
                ->map(fn ($id) => is_numeric($id) ? (int) $id : null)
                ->filter(fn ($id) => !is_null($id))
                ->unique()
                ->values();

            // Get existing users and filter out admin users and current user
            $currentUserId = $request->user()->id;
            $existingUsers = User::whereIn('id', $ids)
                ->where(function($q) {
                    $q->where('role_id', '!=', 1)->orWhereNull('role_id');
                })
                ->where('id', '!=', $currentUserId)
                ->get();

            $existingIds = $existingUsers->pluck('id');
            $missingIds = $ids->diff($existingIds)->values();
            
            // Check for admin users or current user in the list
            $adminOrCurrentUserIds = $ids->filter(function($id) use ($currentUserId) {
                $user = User::find($id);
                return $user && ($user->role_id === 1 || $user->id === $currentUserId);
            })->values();

            if ($existingIds->isEmpty()) {
                return $this->success('No matching membership records found', 200, [
                    'deleted_count' => 0,
                    'deleted_ids' => [],
                    'missing_ids' => $missingIds,
                    'skipped_ids' => $adminOrCurrentUserIds,
                    'message' => 'No membership records were deleted. Some IDs may not exist, be admin users, or be your own account.'
                ]);
            }

            // Soft delete the memberships
            $deletedCount = User::whereIn('id', $existingIds)->delete();

            return $this->success('Membership records deleted successfully', 200, [
                'deleted_count' => $deletedCount,
                'deleted_ids' => $existingIds->values(),
                'missing_ids' => $missingIds,
                'skipped_ids' => $adminOrCurrentUserIds,
                'message' => "{$deletedCount} membership record(s) have been moved to trash"
            ]);

        } catch (\Throwable $e) {
            return $this->error('Failed to delete membership records', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Restore soft deleted membership record
     */
    public function restoreMembership(Request $request, $id)
    {
        try {
            $user = User::onlyTrashed()->find($id);

            if (!$user) {
                return $this->error('Deleted membership record not found', 404, [
                    'message' => 'No deleted membership record found with the given ID'
                ]);
            }

            $user->restore();

            return $this->success('Membership record restored successfully', 200, [
                'message' => 'Membership record has been restored',
                'membership' => $user->fresh()
            ]);

        } catch (\Throwable $e) {
            return $this->error('Failed to restore membership record', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Get trashed (deleted) membership records
     */
    public function getTrashedMemberships(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search');
            $sortBy = $request->get('sort_by', 'deleted_at');
            $sortOrder = $request->get('sort_order', 'desc');

            $query = User::onlyTrashed();

            // Search functionality
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%")
                      ->orWhere('mobile', 'LIKE', "%{$search}%")
                      ->orWhere('m_id', 'LIKE', "%{$search}%");
                });
            }

            // Only get users with membership data (exclude admin users)
            $query->where(function($q) {
                $q->where('role_id', '!=', 1)->orWhereNull('role_id');
            });

            // Sorting
            $query->orderBy($sortBy, $sortOrder);

            $trashed = $query->paginate($perPage);

            return $this->success('Trashed membership records retrieved successfully', 200, [
                'memberships' => $trashed->items(),
                'pagination' => [
                    'current_page' => $trashed->currentPage(),
                    'last_page' => $trashed->lastPage(),
                    'per_page' => $trashed->perPage(),
                    'total' => $trashed->total(),
                    'from' => $trashed->firstItem(),
                    'to' => $trashed->lastItem(),
                ]
            ]);

        } catch (\Throwable $e) {
            return $this->error('Failed to retrieve trashed membership records', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Get active banners for website (public endpoint)
     */
    public function getWebsiteBanners(Request $request)
    {
        try {
            $now = now();

            $query = Banner::where('is_active', true)
                ->where(function ($q) use ($now) {
                    $q->whereNull('starts_at')
                      ->orWhere('starts_at', '<=', $now);
                })
                ->where(function ($q) use ($now) {
                    $q->whereNull('ends_at')
                      ->orWhere('ends_at', '>=', $now);
                });

            // Optional limit
            $limit = $request->get('limit');
            if ($limit && is_numeric($limit)) {
                $query->limit((int)$limit);
            }

            // Sort by sort_order ascending, then by created_at descending
            $banners = $query->orderBy('sort_order', 'asc')
                ->orderBy('created_at', 'desc')
                ->get();

            // Map with image_url for convenience
            $items = $banners->map(function ($b) {
                return [
                    'id' => $b->id,
                    'title' => $b->title,
                    'image_url' => $b->image_path ? url($b->image_path) : null,
                    'link_url' => $b->link_url,
                    'sort_order' => $b->sort_order,
                    'starts_at' => $b->starts_at,
                    'ends_at' => $b->ends_at,
                ];
            });

            return $this->success('Banners retrieved successfully', 200, [
                'banners' => $items,
                'total' => $items->count()
            ]);
        } catch (\Throwable $e) {
            return $this->error('Failed to retrieve banners', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Event Management
     */
    public function getEventList(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search');
            $sortBy = $request->get('sort_by', 'sort_order');
            $sortOrder = $request->get('sort_order', 'asc');
            $isActive = $request->get('is_active');
            $eventType = $request->get('event_type');

            $query = Event::query();

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%{$search}%")
                      ->orWhere('location', 'LIKE', "%{$search}%")
                      ->orWhere('description', 'LIKE', "%{$search}%");
                });
            }

            if ($isActive !== null) {
                $query->where('is_active', filter_var($isActive, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE));
            }

            if ($eventType) {
                $query->where('event_type', $eventType);
            }

            $query->orderBy($sortBy, $sortOrder);

            $events = $query->paginate($perPage);

            return $this->success('Events retrieved successfully', 200, [
                'events' => $events->items(),
                'pagination' => [
                    'current_page' => $events->currentPage(),
                    'last_page' => $events->lastPage(),
                    'per_page' => $events->perPage(),
                    'total' => $events->total(),
                    'from' => $events->firstItem(),
                    'to' => $events->lastItem(),
                ]
            ]);
        } catch (\Throwable $e) {
            return $this->error('Failed to retrieve events', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    public function getEvent(Request $request, $id)
    {
        try {
            $event = Event::find($id);
            if (!$event) {
                return $this->error('Event not found', 404, [
                    'message' => 'No event found with the given ID'
                ]);
            }
            return $this->success('Event retrieved successfully', 200, [
                'event' => $event
            ]);
        } catch (\Throwable $e) {
            return $this->error('Failed to retrieve event', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    public function createEvent(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'location' => 'required|string|max:255',
                'event_date' => 'nullable|date',
                'event_date_end' => 'nullable|date|after_or_equal:event_date',
                'description' => 'nullable|string',
                'link_url' => 'nullable|url',
                'event_type' => 'nullable|string|max:100',
                'is_active' => 'nullable|boolean',
                'sort_order' => 'nullable|integer|min:0',
                'starts_at' => 'nullable|date',
                'ends_at' => 'nullable|date|after_or_equal:starts_at',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            $event = Event::create($request->only([
                'title', 'location', 'event_date', 'event_date_end', 'description',
                'link_url', 'event_type', 'is_active', 'sort_order', 'starts_at', 'ends_at'
            ]));

            return $this->success('Event created successfully', 201, [
                'event' => $event
            ]);
        } catch (\Throwable $e) {
            return $this->error('Failed to create event', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    public function updateEvent(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|string|max:255',
                'location' => 'sometimes|string|max:255',
                'event_date' => 'nullable|date',
                'event_date_end' => 'nullable|date|after_or_equal:event_date',
                'description' => 'nullable|string',
                'link_url' => 'nullable|url',
                'event_type' => 'nullable|string|max:100',
                'is_active' => 'nullable|boolean',
                'sort_order' => 'nullable|integer|min:0',
                'starts_at' => 'nullable|date',
                'ends_at' => 'nullable|date|after_or_equal:starts_at',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            $event = Event::find($id);
            if (!$event) {
                return $this->error('Event not found', 404, [
                    'message' => 'No event found with the given ID'
                ]);
            }

            $event->update($request->only([
                'title', 'location', 'event_date', 'event_date_end', 'description',
                'link_url', 'event_type', 'is_active', 'sort_order', 'starts_at', 'ends_at'
            ]));

            return $this->success('Event updated successfully', 200, [
                'event' => $event->fresh()
            ]);
        } catch (\Throwable $e) {
            return $this->error('Failed to update event', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    public function deleteEvent(Request $request, $id)
    {
        try {
            $event = Event::find($id);
            if (!$event) {
                return $this->error('Event not found', 404, [
                    'message' => 'No event found with the given ID'
                ]);
            }
            $event->delete();
            return $this->success('Event deleted successfully', 200, [
                'message' => 'Event has been permanently deleted'
            ]);
        } catch (\Throwable $e) {
            return $this->error('Failed to delete event', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    public function bulkDeleteEvent(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array|min:1',
                'ids.*' => 'integer|exists:events,id'
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            $deletedCount = Event::whereIn('id', $request->ids)->delete();

            return $this->success('Events deleted successfully', 200, [
                'deleted_count' => $deletedCount,
                'message' => "{$deletedCount} events have been permanently deleted"
            ]);
        } catch (\Throwable $e) {
            return $this->error('Failed to delete events', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Get upcoming conference for website (public endpoint)
     * Returns the first active conference event
     */
    public function getWebsiteConference(Request $request)
    {
        try {
            $now = now();

            $query = Event::where('is_active', true)
                ->where('event_type', 'conference')
                ->where(function ($q) use ($now) {
                    $q->whereNull('starts_at')
                      ->orWhere('starts_at', '<=', $now);
                })
                ->where(function ($q) use ($now) {
                    $q->whereNull('ends_at')
                      ->orWhere('ends_at', '>=', $now);
                });

            // Get the first conference (sorted by sort_order)
            $conference = $query->orderBy('sort_order', 'asc')
                ->orderBy('event_date', 'asc')
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$conference) {
                return $this->success('No upcoming conference', 200, [
                    'conference' => null
                ]);
            }

            // Format event date for display
            $dateDisplay = '';
            if ($conference->event_date) {
                if ($conference->event_date_end && $conference->event_date_end != $conference->event_date) {
                    // Date range
                    $dateDisplay = $conference->event_date->format('d F Y') . ' - ' . $conference->event_date_end->format('d F Y');
                } else {
                    // Single date
                    $dateDisplay = $conference->event_date->format('d F Y');
                }
            } else {
                $dateDisplay = 'TBA';
            }

            $conferenceData = [
                'id' => $conference->id,
                'title' => $conference->title,
                'location' => $conference->location,
                'event_date' => $conference->event_date ? $conference->event_date->format('Y-m-d') : null,
                'event_date_end' => $conference->event_date_end ? $conference->event_date_end->format('Y-m-d') : null,
                'date_display' => $dateDisplay,
                'description' => $conference->description,
                'link_url' => $conference->link_url,
                'event_type' => $conference->event_type,
                'sort_order' => $conference->sort_order,
            ];

            return $this->success('Conference retrieved successfully', 200, [
                'conference' => $conferenceData
            ]);
        } catch (\Throwable $e) {
            return $this->error('Failed to retrieve conference', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Get active events for website (public endpoint)
     * Excludes conference events by default (can be included with exclude_conference=false)
     */
    public function getWebsiteEvents(Request $request)
    {
        try {
            $now = now();

            $query = Event::where('is_active', true)
                ->where(function ($q) use ($now) {
                    $q->whereNull('starts_at')
                      ->orWhere('starts_at', '<=', $now);
                })
                ->where(function ($q) use ($now) {
                    $q->whereNull('ends_at')
                      ->orWhere('ends_at', '>=', $now);
                });

            // Exclude conference events by default (for home page events list)
            $excludeConference = $request->get('exclude_conference', 'true');
            if ($excludeConference === 'true' || $excludeConference === true) {
                $query->where(function ($q) {
                    $q->where('event_type', '!=', 'conference')
                      ->orWhereNull('event_type');
                });
            }

            // Optional limit
            $limit = $request->get('limit');
            if ($limit && is_numeric($limit)) {
                $query->limit((int)$limit);
            }

            // Optional event type filter
            $eventType = $request->get('event_type');
            if ($eventType) {
                $query->where('event_type', $eventType);
            }

            // Sort by sort_order ascending, then by event_date ascending
            $events = $query->orderBy('sort_order', 'asc')
                ->orderBy('event_date', 'asc')
                ->orderBy('created_at', 'desc')
                ->get();

            // Format event date for display
            $items = $events->map(function ($e) {
                $dateDisplay = '';
                if ($e->event_date) {
                    if ($e->event_date_end && $e->event_date_end != $e->event_date) {
                        // Date range
                        $dateDisplay = $e->event_date->format('d F Y') . ' - ' . $e->event_date_end->format('d F Y');
                    } else {
                        // Single date
                        $dateDisplay = $e->event_date->format('d F Y');
                    }
                } else {
                    $dateDisplay = 'TBA';
                }

                return [
                    'id' => $e->id,
                    'title' => $e->title,
                    'location' => $e->location,
                    'event_date' => $e->event_date ? $e->event_date->format('Y-m-d') : null,
                    'event_date_end' => $e->event_date_end ? $e->event_date_end->format('Y-m-d') : null,
                    'date_display' => $dateDisplay,
                    'description' => $e->description,
                    'link_url' => $e->link_url,
                    'event_type' => $e->event_type,
                    'sort_order' => $e->sort_order,
                ];
            });

            return $this->success('Events retrieved successfully', 200, [
                'events' => $items,
                'total' => $items->count()
            ]);
        } catch (\Throwable $e) {
            return $this->error('Failed to retrieve events', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Get all partners with pagination
     */
    public function getPartnerList(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search');
            $sortBy = $request->get('sort_by', 'sort_order');
            $sortOrder = $request->get('sort_order', 'asc');
            $isActive = $request->get('is_active');

            $query = Partner::query();

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('subtitle', 'LIKE', "%{$search}%");
                });
            }

            if ($isActive !== null) {
                $query->where('is_active', filter_var($isActive, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE));
            }

            $query->orderBy($sortBy, $sortOrder);

            $partners = $query->paginate($perPage);

            // Map with logo_url for convenience
            $items = collect($partners->items())->map(function ($p) {
                $p->logo_url = $p->logo_path ? url($p->logo_path) : null;
                return $p;
            });

            return $this->success('Partners retrieved successfully', 200, [
                'partners' => $items,
                'pagination' => [
                    'current_page' => $partners->currentPage(),
                    'last_page' => $partners->lastPage(),
                    'per_page' => $partners->perPage(),
                    'total' => $partners->total(),
                    'from' => $partners->firstItem(),
                    'to' => $partners->lastItem(),
                ]
            ]);
        } catch (\Throwable $e) {
            return $this->error('Failed to retrieve partners', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    public function getPartner(Request $request, $id)
    {
        try {
            $partner = Partner::find($id);
            if (!$partner) {
                return $this->error('Partner not found', 404, [
                    'message' => 'No partner found with the given ID'
                ]);
            }
            $partner->logo_url = $partner->logo_path ? url($partner->logo_path) : null;
            return $this->success('Partner retrieved successfully', 200, [
                'partner' => $partner
            ]);
        } catch (\Throwable $e) {
            return $this->error('Failed to retrieve partner', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    public function createPartner(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'logo' => 'required|image|mimes:jpg,jpeg,png,webp,svg|max:5120',
                'subtitle' => 'nullable|string|max:255',
                'link_url' => 'nullable|url',
                'is_active' => 'nullable|boolean',
                'sort_order' => 'nullable|integer|min:0',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            $logoPath = null;
            if ($request->hasFile('logo')) {
                $stored = $request->file('logo')->store('partners', 'public');
                $logoPath = 'storage/' . $stored; // public URL path
            }

            $partner = Partner::create([
                'name' => $request->name,
                'logo_path' => $logoPath,
                'subtitle' => $request->subtitle,
                'link_url' => $request->link_url,
                'is_active' => $request->boolean('is_active', true),
                'sort_order' => $request->input('sort_order', 0),
            ]);

            $partner->logo_url = $partner->logo_path ? url($partner->logo_path) : null;

            return $this->success('Partner created successfully', 201, [
                'partner' => $partner
            ]);
        } catch (\Throwable $e) {
            return $this->error('Failed to create partner', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    public function updatePartner(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'logo' => 'sometimes|image|mimes:jpg,jpeg,png,webp,svg|max:5120',
                'subtitle' => 'nullable|string|max:255',
                'link_url' => 'nullable|url',
                'is_active' => 'nullable|boolean',
                'sort_order' => 'nullable|integer|min:0',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            $partner = Partner::find($id);
            if (!$partner) {
                return $this->error('Partner not found', 404, [
                    'message' => 'No partner found with the given ID'
                ]);
            }

            $updateData = $request->only(['name', 'subtitle', 'link_url', 'is_active', 'sort_order']);

            if ($request->hasFile('logo')) {
                // Delete old logo if exists
                if ($partner->logo_path && file_exists(public_path($partner->logo_path))) {
                    @unlink(public_path($partner->logo_path));
                }
                $stored = $request->file('logo')->store('partners', 'public');
                $updateData['logo_path'] = 'storage/' . $stored;
            }

            $partner->update($updateData);
            $partner->logo_url = $partner->logo_path ? url($partner->logo_path) : null;

            return $this->success('Partner updated successfully', 200, [
                'partner' => $partner
            ]);
        } catch (\Throwable $e) {
            return $this->error('Failed to update partner', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    public function deletePartner(Request $request, $id)
    {
        try {
            $partner = Partner::find($id);
            if (!$partner) {
                return $this->error('Partner not found', 404, [
                    'message' => 'No partner found with the given ID'
                ]);
            }

            // Delete logo file if exists
            if ($partner->logo_path && file_exists(public_path($partner->logo_path))) {
                @unlink(public_path($partner->logo_path));
            }

            $partner->delete();

            return $this->success('Partner deleted successfully', 200, []);
        } catch (\Throwable $e) {
            return $this->error('Failed to delete partner', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    public function bulkDeletePartner(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array',
                'ids.*' => 'required|integer|exists:partners,id',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            $partners = Partner::whereIn('id', $request->ids)->get();

            foreach ($partners as $partner) {
                // Delete logo file if exists
                if ($partner->logo_path && file_exists(public_path($partner->logo_path))) {
                    @unlink(public_path($partner->logo_path));
                }
            }

            Partner::whereIn('id', $request->ids)->delete();

            return $this->success('Partners deleted successfully', 200, []);
        } catch (\Throwable $e) {
            return $this->error('Failed to delete partners', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Get active partners for website (public endpoint)
     */
    public function getWebsitePartners(Request $request)
    {
        try {
            $query = Partner::where('is_active', true);

            // Optional limit
            $limit = $request->get('limit');
            if ($limit && is_numeric($limit)) {
                $query->limit((int)$limit);
            }

            // Sort by sort_order ascending
            $partners = $query->orderBy('sort_order', 'asc')
                ->orderBy('created_at', 'desc')
                ->get();

            $items = $partners->map(function ($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'logo_url' => $p->logo_path ? url($p->logo_path) : null,
                    'subtitle' => $p->subtitle,
                    'link_url' => $p->link_url,
                    'sort_order' => $p->sort_order,
                ];
            });

            return $this->success('Partners retrieved successfully', 200, [
                'partners' => $items,
                'total' => $items->count()
            ]);
        } catch (\Throwable $e) {
            return $this->error('Failed to retrieve partners', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Get all galleries with pagination
     */
    public function getGalleryList(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search');
            $sortBy = $request->get('sort_by', 'sort_order');
            $sortOrder = $request->get('sort_order', 'asc');
            $isActive = $request->get('is_active');

            $query = Gallery::query();

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%{$search}%");
                });
            }

            if ($isActive !== null) {
                $query->where('is_active', filter_var($isActive, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE));
            }

            $query->orderBy($sortBy, $sortOrder);

            $galleries = $query->paginate($perPage);

            // Map with image_url for convenience
            $items = collect($galleries->items())->map(function ($g) {
                $g->image_url = $g->image_path ? url($g->image_path) : null;
                return $g;
            });

            return $this->success('Galleries retrieved successfully', 200, [
                'galleries' => $items,
                'pagination' => [
                    'current_page' => $galleries->currentPage(),
                    'last_page' => $galleries->lastPage(),
                    'per_page' => $galleries->perPage(),
                    'total' => $galleries->total(),
                    'from' => $galleries->firstItem(),
                    'to' => $galleries->lastItem(),
                ]
            ]);
        } catch (\Throwable $e) {
            return $this->error('Failed to retrieve galleries', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    public function getGallery(Request $request, $id)
    {
        try {
            $gallery = Gallery::find($id);
            if (!$gallery) {
                return $this->error('Gallery not found', 404, [
                    'message' => 'No gallery found with the given ID'
                ]);
            }
            $gallery->image_url = $gallery->image_path ? url($gallery->image_path) : null;
            return $this->success('Gallery retrieved successfully', 200, [
                'gallery' => $gallery
            ]);
        } catch (\Throwable $e) {
            return $this->error('Failed to retrieve gallery', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    public function createGallery(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'image' => 'required|image|mimes:jpg,jpeg,png,webp,svg|max:5120',
                'is_active' => 'nullable|boolean',
                'sort_order' => 'nullable|integer|min:0',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            $imagePath = null;
            if ($request->hasFile('image')) {
                $stored = $request->file('image')->store('galleries', 'public');
                $imagePath = 'storage/' . $stored; // public URL path
            }

            $gallery = Gallery::create([
                'title' => $request->title,
                'image_path' => $imagePath,
                'is_active' => $request->boolean('is_active', true),
                'sort_order' => $request->input('sort_order', 0),
            ]);

            $gallery->image_url = $gallery->image_path ? url($gallery->image_path) : null;

            return $this->success('Gallery created successfully', 201, [
                'gallery' => $gallery
            ]);
        } catch (\Throwable $e) {
            return $this->error('Failed to create gallery', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    public function updateGallery(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|string|max:255',
                'image' => 'sometimes|image|mimes:jpg,jpeg,png,webp,svg|max:5120',
                'is_active' => 'nullable|boolean',
                'sort_order' => 'nullable|integer|min:0',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            $gallery = Gallery::find($id);
            if (!$gallery) {
                return $this->error('Gallery not found', 404, [
                    'message' => 'No gallery found with the given ID'
                ]);
            }

            $updateData = $request->only(['title', 'is_active', 'sort_order']);

            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($gallery->image_path && file_exists(public_path($gallery->image_path))) {
                    @unlink(public_path($gallery->image_path));
                }
                $stored = $request->file('image')->store('galleries', 'public');
                $updateData['image_path'] = 'storage/' . $stored;
            }

            $gallery->update($updateData);
            $gallery->image_url = $gallery->image_path ? url($gallery->image_path) : null;

            return $this->success('Gallery updated successfully', 200, [
                'gallery' => $gallery
            ]);
        } catch (\Throwable $e) {
            return $this->error('Failed to update gallery', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    public function deleteGallery(Request $request, $id)
    {
        try {
            $gallery = Gallery::find($id);
            if (!$gallery) {
                return $this->error('Gallery not found', 404, [
                    'message' => 'No gallery found with the given ID'
                ]);
            }

            // Delete image file if exists
            if ($gallery->image_path && file_exists(public_path($gallery->image_path))) {
                @unlink(public_path($gallery->image_path));
            }

            $gallery->delete();

            return $this->success('Gallery deleted successfully', 200, []);
        } catch (\Throwable $e) {
            return $this->error('Failed to delete gallery', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    public function bulkDeleteGallery(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array',
                'ids.*' => 'required|integer|exists:galleries,id',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            $galleries = Gallery::whereIn('id', $request->ids)->get();

            foreach ($galleries as $gallery) {
                // Delete image file if exists
                if ($gallery->image_path && file_exists(public_path($gallery->image_path))) {
                    @unlink(public_path($gallery->image_path));
                }
            }

            Gallery::whereIn('id', $request->ids)->delete();

            return $this->success('Galleries deleted successfully', 200, []);
        } catch (\Throwable $e) {
            return $this->error('Failed to delete galleries', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Get active galleries for website (public endpoint)
     */
    public function getWebsiteGalleries(Request $request)
    {
        try {
            $query = Gallery::where('is_active', true);

            // Optional limit
            $limit = $request->get('limit');
            if ($limit && is_numeric($limit)) {
                $query->limit((int)$limit);
            }

            // Sort by sort_order ascending
            $galleries = $query->orderBy('sort_order', 'asc')
                ->orderBy('created_at', 'desc')
                ->get();

            $items = $galleries->map(function ($g) {
                return [
                    'id' => $g->id,
                    'title' => $g->title,
                    'image_url' => $g->image_path ? url($g->image_path) : null,
                    'sort_order' => $g->sort_order,
                ];
            });

            return $this->success('Galleries retrieved successfully', 200, [
                'galleries' => $items,
                'total' => $items->count()
            ]);
        } catch (\Throwable $e) {
            return $this->error('Failed to retrieve galleries', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Subscribe to newsletter (public endpoint)
     */
    public function subscribeNewsletter(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'whatsapp_no' => 'nullable|string|max:20',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            // Check if email already exists
            $existing = Newsletter::where('email', $request->email)->first();
            if ($existing) {
                return $this->error('Email already subscribed', 409, [
                    'message' => 'This email is already subscribed to our newsletter'
                ]);
            }

            $newsletter = Newsletter::create([
                'name' => $request->name,
                'email' => $request->email,
                'whatsapp_no' => $request->whatsapp_no,
            ]);

            return $this->success('Successfully subscribed to newsletter', 201, [
                'newsletter' => $newsletter
            ]);
        } catch (\Throwable $e) {
            return $this->error('Failed to subscribe', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Get all newsletter subscriptions with pagination (admin)
     */
    public function getNewsletterList(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search');
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');

            $query = Newsletter::query();

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%")
                      ->orWhere('whatsapp_no', 'LIKE', "%{$search}%");
                });
            }

            $query->orderBy($sortBy, $sortOrder);

            $newsletters = $query->paginate($perPage);

            return $this->success('Newsletters retrieved successfully', 200, [
                'newsletters' => $newsletters->items(),
                'pagination' => [
                    'current_page' => $newsletters->currentPage(),
                    'last_page' => $newsletters->lastPage(),
                    'per_page' => $newsletters->perPage(),
                    'total' => $newsletters->total(),
                    'from' => $newsletters->firstItem(),
                    'to' => $newsletters->lastItem(),
                ]
            ]);
        } catch (\Throwable $e) {
            return $this->error('Failed to retrieve newsletters', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    public function getNewsletterSubscription(Request $request, $id)
    {
        try {
            $newsletter = Newsletter::find($id);
            if (!$newsletter) {
                return $this->error('Newsletter subscription not found', 404, [
                    'message' => 'No newsletter subscription found with the given ID'
                ]);
            }
            return $this->success('Newsletter retrieved successfully', 200, [
                'newsletter' => $newsletter
            ]);
        } catch (\Throwable $e) {
            return $this->error('Failed to retrieve newsletter', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    public function deleteNewsletter(Request $request, $id)
    {
        try {
            $newsletter = Newsletter::find($id);
            if (!$newsletter) {
                return $this->error('Newsletter subscription not found', 404, [
                    'message' => 'No newsletter subscription found with the given ID'
                ]);
            }

            $newsletter->delete();

            return $this->success('Newsletter subscription deleted successfully', 200, []);
        } catch (\Throwable $e) {
            return $this->error('Failed to delete newsletter', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    public function bulkDeleteNewsletter(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array',
                'ids.*' => 'required|integer|exists:newsletters,id',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            Newsletter::whereIn('id', $request->ids)->delete();

            return $this->success('Newsletter subscriptions deleted successfully', 200, []);
        } catch (\Throwable $e) {
            return $this->error('Failed to delete newsletters', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Get all news with pagination (admin)
     */
    public function getNewsList(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search');
            $sortBy = $request->get('sort_by', 'sort_order');
            $sortOrder = $request->get('sort_order', 'asc');
            $isActive = $request->get('is_active');

            $query = News::query();

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%{$search}%")
                      ->orWhere('publisher_name', 'LIKE', "%{$search}%")
                      ->orWhere('conference_name', 'LIKE', "%{$search}%")
                      ->orWhere('summary', 'LIKE', "%{$search}%");
                });
            }

            if ($isActive !== null) {
                $query->where('is_active', filter_var($isActive, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE));
            }

            $query->orderBy($sortBy, $sortOrder);

            $news = $query->paginate($perPage);

            // Map with publisher_logo_url for convenience
            $items = collect($news->items())->map(function ($n) {
                $n->publisher_logo_url = $n->publisher_logo_path ? url($n->publisher_logo_path) : null;
                return $n;
            });

            return $this->success('News retrieved successfully', 200, [
                'news' => $items,
                'pagination' => [
                    'current_page' => $news->currentPage(),
                    'last_page' => $news->lastPage(),
                    'per_page' => $news->perPage(),
                    'total' => $news->total(),
                    'from' => $news->firstItem(),
                    'to' => $news->lastItem(),
                ]
            ]);
        } catch (\Throwable $e) {
            return $this->error('Failed to retrieve news', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    public function getNews(Request $request, $id)
    {
        try {
            $news = News::find($id);
            if (!$news) {
                return $this->error('News not found', 404, [
                    'message' => 'No news found with the given ID'
                ]);
            }
            $news->publisher_logo_url = $news->publisher_logo_path ? url($news->publisher_logo_path) : null;
            return $this->success('News retrieved successfully', 200, [
                'news' => $news
            ]);
        } catch (\Throwable $e) {
            return $this->error('Failed to retrieve news', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    public function createNews(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'publisher_name' => 'required|string|max:255',
                'title' => 'required|string|max:255',
                'summary' => 'required|string',
                'location' => 'nullable|string|max:255',
                'publisher_logo' => 'nullable|image|mimes:jpg,jpeg,png,webp,svg|max:5120',
                'conference_name' => 'nullable|string|max:255',
                'has_video' => 'nullable|boolean',
                'view_count' => 'nullable|integer|min:0',
                'link_url' => 'nullable|url',
                'published_date' => 'nullable|date',
                'is_active' => 'nullable|boolean',
                'sort_order' => 'nullable|integer|min:0',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            $publisherLogoPath = null;
            if ($request->hasFile('publisher_logo')) {
                $stored = $request->file('publisher_logo')->store('news', 'public');
                $publisherLogoPath = 'storage/' . $stored;
            }

            $news = News::create([
                'location' => $request->location,
                'publisher_name' => $request->publisher_name,
                'publisher_logo_path' => $publisherLogoPath,
                'conference_name' => $request->conference_name,
                'title' => $request->title,
                'summary' => $request->summary,
                'has_video' => $request->boolean('has_video', false),
                'view_count' => $request->input('view_count', 0),
                'link_url' => $request->link_url,
                'published_date' => $request->published_date,
                'is_active' => $request->boolean('is_active', true),
                'sort_order' => $request->input('sort_order', 0),
            ]);

            $news->publisher_logo_url = $news->publisher_logo_path ? url($news->publisher_logo_path) : null;

            return $this->success('News created successfully', 201, [
                'news' => $news
            ]);
        } catch (\Throwable $e) {
            return $this->error('Failed to create news', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    public function updateNews(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'publisher_name' => 'sometimes|string|max:255',
                'title' => 'sometimes|string|max:255',
                'summary' => 'sometimes|string',
                'location' => 'nullable|string|max:255',
                'publisher_logo' => 'nullable|image|mimes:jpg,jpeg,png,webp,svg|max:5120',
                'conference_name' => 'nullable|string|max:255',
                'has_video' => 'nullable|boolean',
                'view_count' => 'nullable|integer|min:0',
                'link_url' => 'nullable|url',
                'published_date' => 'nullable|date',
                'is_active' => 'nullable|boolean',
                'sort_order' => 'nullable|integer|min:0',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            $news = News::find($id);
            if (!$news) {
                return $this->error('News not found', 404, [
                    'message' => 'No news found with the given ID'
                ]);
            }

            $updateData = $request->only(['location', 'publisher_name', 'conference_name', 'title', 'summary', 'has_video', 'view_count', 'link_url', 'published_date', 'is_active', 'sort_order']);

            if ($request->hasFile('publisher_logo')) {
                // Delete old logo if exists
                if ($news->publisher_logo_path && file_exists(public_path($news->publisher_logo_path))) {
                    @unlink(public_path($news->publisher_logo_path));
                }
                $stored = $request->file('publisher_logo')->store('news', 'public');
                $updateData['publisher_logo_path'] = 'storage/' . $stored;
            }

            $news->update($updateData);
            $news->publisher_logo_url = $news->publisher_logo_path ? url($news->publisher_logo_path) : null;

            return $this->success('News updated successfully', 200, [
                'news' => $news
            ]);
        } catch (\Throwable $e) {
            return $this->error('Failed to update news', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    public function deleteNews(Request $request, $id)
    {
        try {
            $news = News::find($id);
            if (!$news) {
                return $this->error('News not found', 404, [
                    'message' => 'No news found with the given ID'
                ]);
            }

            // Delete publisher logo file if exists
            if ($news->publisher_logo_path && file_exists(public_path($news->publisher_logo_path))) {
                @unlink(public_path($news->publisher_logo_path));
            }

            $news->delete();

            return $this->success('News deleted successfully', 200, []);
        } catch (\Throwable $e) {
            return $this->error('Failed to delete news', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    public function bulkDeleteNews(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array',
                'ids.*' => 'required|integer|exists:news,id',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            $news = News::whereIn('id', $request->ids)->get();

            foreach ($news as $n) {
                // Delete publisher logo file if exists
                if ($n->publisher_logo_path && file_exists(public_path($n->publisher_logo_path))) {
                    @unlink(public_path($n->publisher_logo_path));
                }
            }

            News::whereIn('id', $request->ids)->delete();

            return $this->success('News deleted successfully', 200, []);
        } catch (\Throwable $e) {
            return $this->error('Failed to delete news', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Get active news for website (public endpoint)
     */
    public function getWebsiteNews(Request $request)
    {
        try {
            $query = News::where('is_active', true);

            // Optional limit
            $limit = $request->get('limit');
            if ($limit && is_numeric($limit)) {
                $query->limit((int)$limit);
            }

            // Sort by sort_order ascending
            $news = $query->orderBy('sort_order', 'asc')
                ->orderBy('published_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            $items = $news->map(function ($n) {
                return [
                    'id' => $n->id,
                    'location' => $n->location,
                    'publisher_name' => $n->publisher_name,
                    'publisher_logo_url' => $n->publisher_logo_path ? url($n->publisher_logo_path) : null,
                    'conference_name' => $n->conference_name,
                    'title' => $n->title,
                    'summary' => $n->summary,
                    'has_video' => $n->has_video,
                    'view_count' => $n->view_count,
                    'link_url' => $n->link_url,
                    'published_date' => $n->published_date ? $n->published_date->format('Y-m-d') : null,
                    'sort_order' => $n->sort_order,
                ];
            });

            return $this->success('News retrieved successfully', 200, [
                'news' => $items,
                'total' => $items->count()
            ]);
        } catch (\Throwable $e) {
            return $this->error('Failed to retrieve news', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Get active highlights for website
     */
    public function getWebsiteHighlights(Request $request)
    {
        try {
            $highlight = Highlight::where('is_active', true)
                ->orderBy('sort_order', 'asc')
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$highlight) {
                // Return default values if no highlight exists
                return $this->success('Highlight retrieved successfully', 200, [
                    'highlight' => [
                        'heading' => 'HIGHLIGHTS',
                        'subheading' => '9th AINET International Conference 2026 - To Be Announced SOON'
                    ]
                ]);
            }

            return $this->success('Highlight retrieved successfully', 200, [
                'highlight' => [
                    'id' => $highlight->id,
                    'heading' => $highlight->heading,
                    'subheading' => $highlight->subheading,
                ]
            ]);
        } catch (\Throwable $e) {
            return $this->error('Failed to retrieve highlight', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Get all highlights (admin)
     */
    public function getHighlights(Request $request)
    {
        try {
            $highlights = Highlight::orderBy('sort_order', 'asc')
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->success('Highlights retrieved successfully', 200, [
                'highlights' => $highlights
            ]);
        } catch (\Throwable $e) {
            return $this->error('Failed to retrieve highlights', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Get single highlight (admin)
     */
    public function getHighlight($id)
    {
        try {
            $highlight = Highlight::find($id);

            if (!$highlight) {
                return $this->error('Highlight not found', 404);
            }

            return $this->success('Highlight retrieved successfully', 200, [
                'highlight' => $highlight
            ]);
        } catch (\Throwable $e) {
            return $this->error('Failed to retrieve highlight', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Create highlight (admin)
     */
    public function createHighlight(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'heading' => 'required|string|max:255',
                'subheading' => 'required|string',
                'is_active' => 'sometimes|boolean',
                'sort_order' => 'sometimes|integer',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            $highlight = Highlight::create($request->all());

            return $this->success('Highlight created successfully', 201, [
                'highlight' => $highlight
            ]);
        } catch (\Throwable $e) {
            return $this->error('Failed to create highlight', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Update highlight (admin)
     */
    public function updateHighlight(Request $request, $id)
    {
        try {
            $highlight = Highlight::find($id);

            if (!$highlight) {
                return $this->error('Highlight not found', 404);
            }

            $validator = Validator::make($request->all(), [
                'heading' => 'sometimes|string|max:255',
                'subheading' => 'sometimes|string',
                'is_active' => 'sometimes|boolean',
                'sort_order' => 'sometimes|integer',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            $highlight->update($request->all());

            return $this->success('Highlight updated successfully', 200, [
                'highlight' => $highlight->fresh()
            ]);
        } catch (\Throwable $e) {
            return $this->error('Failed to update highlight', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Delete highlight (admin)
     */
    public function deleteHighlight($id)
    {
        try {
            $highlight = Highlight::find($id);

            if (!$highlight) {
                return $this->error('Highlight not found', 404);
            }

            $highlight->delete();

            return $this->success('Highlight deleted successfully', 200);
        } catch (\Throwable $e) {
            return $this->error('Failed to delete highlight', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }
}
