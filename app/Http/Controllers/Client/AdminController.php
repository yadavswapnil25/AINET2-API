<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Traits\Response;
use App\Models\Drf;
use App\Models\Ppf;
use App\Models\User;
use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
                $accessGrant = $user->createToken('Admin Token');

                return $this->success('Admin logged in successfully', 200, [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
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

            $query = Drf::query();

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
            $validator = Validator::make($request->all(), [
                'member' => 'sometimes|string',
                'name' => 'sometimes|string',
                'gender' => 'sometimes|string',
                'age' => 'sometimes|integer|min:0',
                'institution' => 'sometimes|string',
                'address' => 'sometimes|string',
                'city' => 'sometimes|string',
                'pincode' => 'sometimes|string',
                'state' => 'sometimes|string',
                'country_code' => 'sometimes|string',
                'phone_no' => 'sometimes|string',
                'email' => 'sometimes|email',
                'areas' => 'sometimes|string',
                'experience' => 'sometimes|string',
                'conference' => 'sometimes|string',
                'you_are_register_as' => 'sometimes|string',
                'pre_title' => 'sometimes|string',
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

            $drf->update($request->only([
                'member', 'name', 'gender', 'age', 'institution', 'address',
                'city', 'pincode', 'state', 'country_code', 'phone_no', 'email',
                'areas', 'experience', 'conference', 'you_are_register_as', 'pre_title'
            ]));

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
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array|min:1',
                'ids.*' => 'integer|exists:drfs,id'
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            $deletedCount = Drf::whereIn('id', $request->ids)->delete();

            return $this->success('DRF records deleted successfully', 200, [
                'deleted_count' => $deletedCount,
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

            $query = Drf::query();

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
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array|min:1',
                'ids.*' => 'integer|exists:ppfs,id'
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            $deletedCount = Ppf::whereIn('id', $request->ids)->delete();

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
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');

            $query = User::query();

            // Search functionality
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%")
                      ->orWhere('mobile', 'LIKE', "%{$search}%")
                      ->orWhere('m_id', 'LIKE', "%{$search}%")
                      ->orWhere('institution', 'LIKE', "%{$search}%");
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

            $user->delete();

            return $this->success('User deleted successfully', 200, [
                'message' => 'User has been permanently deleted'
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
}
