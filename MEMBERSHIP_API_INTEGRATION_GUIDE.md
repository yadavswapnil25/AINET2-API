# Membership API Integration Guide for React Vite Portal

## API Endpoints

### Base URL
```javascript
const API_BASE_URL = 'http://localhost:8000/api/v1/client';
```

### Available Endpoints

#### 1. Admin Membership List
**GET** `/admin/memberships`

Query Parameters:
- `per_page` - Items per page (default: 15)
- `search` - Search term
- `membership_type` - Filter by type (Individual/Institutional)
- `membership_plan` - Filter by plan (Annual/LongTerm/Overseas)
- `state` - Filter by state
- `start_date` - Filter from date (YYYY-MM-DD)
- `end_date` - Filter to date (YYYY-MM-DD)
- `sort_by` - Sort field (default: created_at)
- `sort_order` - Sort direction (asc/desc, default: desc)

Response:
```json
{
  "status": true,
  "message": "Membership records retrieved successfully",
  "data": {
    "memberships": [...],
    "pagination": {
      "current_page": 1,
      "last_page": 10,
      "per_page": 15,
      "total": 150,
      "from": 1,
      "to": 15
    }
  }
}
```

#### 2. Export Membership CSV
**GET** `/admin/memberships/export`

Same query parameters as list endpoint. Returns CSV file download.

---

## Installation

### Required Dependencies
```bash
npm install axios react-query
# or
yarn add axios react-query
```

---

## Project Structure

```
src/
├── services/
│   ├── api.js                 # Axios configuration
│   └── membershipService.js   # Membership API calls
├── hooks/
│   └── useMemberships.js      # React Query hooks
├── components/
│   └── membership/
│       ├── MembershipList.jsx
│       ├── MembershipFilters.jsx
│       └── MembershipTable.jsx
└── utils/
    └── constants.js           # API constants
```

---

## Implementation Files

### 1. API Configuration (`src/services/api.js`)

```javascript
import axios from 'axios';

const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1/client';

const apiClient = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Request interceptor to add auth token
apiClient.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('admin_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Response interceptor for error handling
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Handle unauthorized - redirect to login
      localStorage.removeItem('admin_token');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export default apiClient;
```

### 2. Membership Service (`src/services/membershipService.js`)

```javascript
import apiClient from './api';

export const membershipService = {
  /**
   * Get paginated membership list with filters
   */
  getMembershipList: async (params = {}) => {
    const response = await apiClient.get('/admin/memberships', { params });
    return response.data;
  },

  /**
   * Export membership data as CSV
   */
  exportMemberships: async (params = {}) => {
    const response = await apiClient.get('/admin/memberships/export', {
      params,
      responseType: 'blob',
    });
    
    // Create download link
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `memberships_${new Date().toISOString().split('T')[0]}.csv`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    
    return response.data;
  },
};
```

### 3. React Query Hooks (`src/hooks/useMemberships.js`)

```javascript
import { useQuery, useMutation } from 'react-query';
import { membershipService } from '../services/membershipService';

/**
 * Hook to fetch membership list with filters
 */
export const useMemberships = (filters = {}) => {
  return useQuery(
    ['memberships', filters],
    () => membershipService.getMembershipList(filters),
    {
      keepPreviousData: true,
      staleTime: 30000, // 30 seconds
      refetchOnWindowFocus: false,
    }
  );
};

/**
 * Hook to export memberships
 */
export const useExportMemberships = () => {
  return useMutation(
    (filters) => membershipService.exportMemberships(filters),
    {
      onSuccess: () => {
        console.log('Export successful');
      },
      onError: (error) => {
        console.error('Export failed:', error);
      },
    }
  );
};
```

### 4. Main Query Provider (`src/main.jsx`)

```javascript
import React from 'react';
import ReactDOM from 'react-dom/client';
import { QueryClient, QueryClientProvider } from 'react-query';
import { ReactQueryDevtools } from 'react-query/devtools';
import App from './App';
import './index.css';

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      refetchOnWindowFocus: false,
      retry: 1,
    },
  },
});

ReactDOM.createRoot(document.getElementById('root')).render(
  <React.StrictMode>
    <QueryClientProvider client={queryClient}>
      <App />
      <ReactQueryDevtools initialIsOpen={false} />
    </QueryClientProvider>
  </React.StrictMode>
);
```

### 5. Membership Filters Component (`src/components/membership/MembershipFilters.jsx`)

```javascript
import React, { useState } from 'react';

const MembershipFilters = ({ onFilterChange, onExport, isExporting }) => {
  const [filters, setFilters] = useState({
    search: '',
    membership_type: '',
    membership_plan: '',
    state: '',
    start_date: '',
    end_date: '',
    per_page: 15,
    sort_by: 'created_at',
    sort_order: 'desc',
  });

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    const newFilters = { ...filters, [name]: value };
    setFilters(newFilters);
  };

  const handleApplyFilters = () => {
    onFilterChange(filters);
  };

  const handleReset = () => {
    const resetFilters = {
      search: '',
      membership_type: '',
      membership_plan: '',
      state: '',
      start_date: '',
      end_date: '',
      per_page: 15,
      sort_by: 'created_at',
      sort_order: 'desc',
    };
    setFilters(resetFilters);
    onFilterChange(resetFilters);
  };

  return (
    <div className="bg-white p-6 rounded-lg shadow-md mb-6">
      <h3 className="text-lg font-semibold mb-4">Filters</h3>
      
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {/* Search */}
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Search
          </label>
          <input
            type="text"
            name="search"
            value={filters.search}
            onChange={handleInputChange}
            placeholder="Name, email, mobile..."
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        {/* Membership Type */}
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Membership Type
          </label>
          <select
            name="membership_type"
            value={filters.membership_type}
            onChange={handleInputChange}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="">All Types</option>
            <option value="Individual">Individual</option>
            <option value="Institutional">Institutional</option>
          </select>
        </div>

        {/* Membership Plan */}
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Membership Plan
          </label>
          <select
            name="membership_plan"
            value={filters.membership_plan}
            onChange={handleInputChange}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="">All Plans</option>
            <option value="Annual">Annual</option>
            <option value="LongTerm">Long Term</option>
            <option value="Overseas">Overseas</option>
          </select>
        </div>

        {/* State */}
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            State
          </label>
          <input
            type="text"
            name="state"
            value={filters.state}
            onChange={handleInputChange}
            placeholder="e.g., Maharashtra"
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        {/* Start Date */}
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Start Date
          </label>
          <input
            type="date"
            name="start_date"
            value={filters.start_date}
            onChange={handleInputChange}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        {/* End Date */}
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            End Date
          </label>
          <input
            type="date"
            name="end_date"
            value={filters.end_date}
            onChange={handleInputChange}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        {/* Per Page */}
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Per Page
          </label>
          <select
            name="per_page"
            value={filters.per_page}
            onChange={handleInputChange}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="10">10</option>
            <option value="15">15</option>
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>

        {/* Sort By */}
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Sort By
          </label>
          <select
            name="sort_by"
            value={filters.sort_by}
            onChange={handleInputChange}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="created_at">Created Date</option>
            <option value="name">Name</option>
            <option value="email">Email</option>
            <option value="membership_type">Type</option>
            <option value="state">State</option>
          </select>
        </div>
      </div>

      {/* Action Buttons */}
      <div className="flex gap-3 mt-4">
        <button
          onClick={handleApplyFilters}
          className="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
        >
          Apply Filters
        </button>
        <button
          onClick={handleReset}
          className="px-6 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-colors"
        >
          Reset
        </button>
        <button
          onClick={() => onExport(filters)}
          disabled={isExporting}
          className="px-6 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed"
        >
          {isExporting ? 'Exporting...' : 'Export CSV'}
        </button>
      </div>
    </div>
  );
};

export default MembershipFilters;
```

### 6. Membership Table Component (`src/components/membership/MembershipTable.jsx`)

```javascript
import React from 'react';

const MembershipTable = ({ memberships, pagination, onPageChange }) => {
  const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
    });
  };

  return (
    <div className="bg-white rounded-lg shadow-md overflow-hidden">
      {/* Table */}
      <div className="overflow-x-auto">
        <table className="min-w-full divide-y divide-gray-200">
          <thead className="bg-gray-50">
            <tr>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                ID
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Name
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Email
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Mobile
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Type
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Plan
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                State
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Created
              </th>
            </tr>
          </thead>
          <tbody className="bg-white divide-y divide-gray-200">
            {memberships.length === 0 ? (
              <tr>
                <td colSpan="8" className="px-6 py-4 text-center text-gray-500">
                  No memberships found
                </td>
              </tr>
            ) : (
              memberships.map((membership) => (
                <tr key={membership.id} className="hover:bg-gray-50">
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {membership.m_id || membership.id}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="text-sm font-medium text-gray-900">
                      {membership.name || `${membership.first_name} ${membership.last_name}`}
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {membership.email}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {membership.mobile || '-'}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <span className="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                      {membership.membership_type || '-'}
                    </span>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {membership.membership_plan || '-'}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {membership.state || '-'}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {formatDate(membership.created_at)}
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>

      {/* Pagination */}
      {pagination && (
        <div className="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
          <div className="flex-1 flex justify-between sm:hidden">
            <button
              onClick={() => onPageChange(pagination.current_page - 1)}
              disabled={pagination.current_page === 1}
              className="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:bg-gray-100 disabled:cursor-not-allowed"
            >
              Previous
            </button>
            <button
              onClick={() => onPageChange(pagination.current_page + 1)}
              disabled={pagination.current_page === pagination.last_page}
              className="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:bg-gray-100 disabled:cursor-not-allowed"
            >
              Next
            </button>
          </div>
          <div className="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
              <p className="text-sm text-gray-700">
                Showing <span className="font-medium">{pagination.from}</span> to{' '}
                <span className="font-medium">{pagination.to}</span> of{' '}
                <span className="font-medium">{pagination.total}</span> results
              </p>
            </div>
            <div>
              <nav className="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                <button
                  onClick={() => onPageChange(pagination.current_page - 1)}
                  disabled={pagination.current_page === 1}
                  className="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:bg-gray-100 disabled:cursor-not-allowed"
                >
                  Previous
                </button>
                <span className="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                  Page {pagination.current_page} of {pagination.last_page}
                </span>
                <button
                  onClick={() => onPageChange(pagination.current_page + 1)}
                  disabled={pagination.current_page === pagination.last_page}
                  className="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:bg-gray-100 disabled:cursor-not-allowed"
                >
                  Next
                </button>
              </nav>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default MembershipTable;
```

### 7. Main Membership List Page (`src/components/membership/MembershipList.jsx`)

```javascript
import React, { useState } from 'react';
import { useMemberships, useExportMemberships } from '../../hooks/useMemberships';
import MembershipFilters from './MembershipFilters';
import MembershipTable from './MembershipTable';

const MembershipList = () => {
  const [filters, setFilters] = useState({
    per_page: 15,
    sort_by: 'created_at',
    sort_order: 'desc',
  });

  const { data, isLoading, isError, error } = useMemberships(filters);
  const { mutate: exportMemberships, isLoading: isExporting } = useExportMemberships();

  const handleFilterChange = (newFilters) => {
    setFilters({ ...newFilters, page: 1 });
  };

  const handlePageChange = (page) => {
    setFilters({ ...filters, page });
  };

  const handleExport = (exportFilters) => {
    exportMemberships(exportFilters);
  };

  if (isError) {
    return (
      <div className="p-6">
        <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
          Error loading memberships: {error?.message || 'Unknown error'}
        </div>
      </div>
    );
  }

  return (
    <div className="container mx-auto p-6">
      <div className="mb-6">
        <h1 className="text-3xl font-bold text-gray-900">Membership Management</h1>
        <p className="text-gray-600 mt-2">View and manage all membership records</p>
      </div>

      <MembershipFilters
        onFilterChange={handleFilterChange}
        onExport={handleExport}
        isExporting={isExporting}
      />

      {isLoading ? (
        <div className="flex justify-center items-center py-12">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        </div>
      ) : (
        <MembershipTable
          memberships={data?.data?.memberships || []}
          pagination={data?.data?.pagination}
          onPageChange={handlePageChange}
        />
      )}
    </div>
  );
};

export default MembershipList;
```

### 8. Environment Variables (`.env`)

```env
VITE_API_URL=http://localhost:8000/api/v1/client
```

---

## Usage in App

```javascript
// src/App.jsx
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import MembershipList from './components/membership/MembershipList';

function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/admin/memberships" element={<MembershipList />} />
        {/* Other routes */}
      </Routes>
    </BrowserRouter>
  );
}

export default App;
```

---

## Tailwind CSS Setup (Optional)

If not already installed:

```bash
npm install -D tailwindcss postcss autoprefixer
npx tailwindcss init -p
```

Update `tailwind.config.js`:
```javascript
export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}
```

---

## Testing

1. Start your Laravel API:
```bash
php artisan serve
```

2. Start your Vite dev server:
```bash
npm run dev
```

3. Navigate to `/admin/memberships`

---

## Additional Features You Can Add

1. **Detail Modal**: Click a row to view full membership details
2. **Edit Functionality**: Update membership records
3. **Delete Confirmation**: Soft delete memberships
4. **Bulk Actions**: Select multiple records for bulk operations
5. **Advanced Search**: Add more search fields
6. **Statistics Dashboard**: Show membership stats with charts

---

## Troubleshooting

### CORS Issues
Add to Laravel's `config/cors.php`:
```php
'paths' => ['api/*'],
'allowed_origins' => ['http://localhost:5173'], // Your Vite dev server
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
'exposed_headers' => [],
'max_age' => 0,
'supports_credentials' => true,
```

### Authentication
Make sure to set the token after login:
```javascript
localStorage.setItem('admin_token', response.data.data.access_token);
```

### Network Errors
Check that your API is running and accessible at the configured URL.

---

## License
This integration guide is provided as-is for the AINET project.

