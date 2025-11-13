# FaculTrack Application - Consolidated Endpoints Documentation

## Overview
This document consolidates all fetch, update, and polling endpoints found across the FaculTrack application, removing duplicates and organizing by functionality.

## 1. POLLING ENDPOINTS (Live Data Updates)

### 1.1 Core Polling Manager (live_polling.js)
The application uses a centralized `LivePollingManager` class that handles real-time updates with configurable intervals:

**Primary Polling Endpoints:**
- `assets/php/polling_api.php?action=get_statistics` (3s intervals)
- `assets/php/polling_api.php?action=get_location_updates` (3s intervals) 
- `assets/php/polling_api.php?action=get_schedule_updates` (3s intervals)
- `assets/php/get_announcements.php` (3s intervals)
- `assets/php/session_heartbeat.php` (15s intervals)

**Conditional Polling Based on Role:**
- **Director/Program Chair:** Table updates via `director.php?action=get_dashboard_data`
- **Class Accounts:** Faculty updates via `home.php?action=get_faculty_updates`
- **Faculty:** Schedule updates and location tracking

## 2. SHARED BACKEND, ROLE-SPECIFIC PRESENTATIONS

### 2.1 Core Architectural Pattern
**Key Insight:** The application implements a sophisticated pattern where **the same backend queries serve multiple user roles, but with completely different frontend presentations**:

- **Director Role:** Data displayed as **tables** with search, sort, expand/collapse, and delete functionality
- **Program Chair Role:** Same data displayed as **interactive cards** with contact actions and workflow buttons  
- **Class Role:** Faculty data displayed as **status-focused cards** with real-time course information

### 2.2 Shared Data Sources

#### Faculty Data - Single Query, Multiple Presentations
**Backend Function:** `getAllFaculty($pdo)` in multiple files
```php
// Same SQL query used across all roles:
SELECT f.faculty_id, u.full_name, f.current_location, f.last_location_update,
       f.office_hours, f.contact_email, f.contact_phone,
       [getFacultyStatusSQL()], [getTimeAgoSQL()]
FROM faculty f JOIN users u ON f.user_id = u.user_id
```

**Frontend Presentations:**
- **Director:** Table rows with status badges, expandable details, delete buttons
- **Program Chair:** Cards with Email/Call/Schedule/Course Load action buttons
- **Class:** Cards focused on current location and today's course status

#### Dashboard Data - Shared Endpoint, Different Views
**Shared Endpoint:** `director.php?action=get_dashboard_data&tab={faculty|classes|courses|announcements|all}`

**Role-Specific Handling:**
- **Director:** Returns data for tabbed table interface with full CRUD operations
- **Program Chair:** Returns same data structure but renders as card-based interface with workflow actions

### 2.3 Campus Director - Table-Based Interface
**Primary Endpoints:**
- `director.php?action=get_dashboard_data&tab={faculty|classes|courses|announcements|all}`
- `director.php?action=get_statistics`

**UI Characteristics:**
- **Data Tables:** Sortable, searchable, expandable rows
- **Bulk Operations:** Delete multiple items, export to CSV
- **Detailed Views:** Expandable rows show additional information
- **Administrative Focus:** Full CRUD operations with confirmation dialogs

**Polling Implementation:**
- 3-second intervals for table updates
- Tab-specific data fetching based on active tab
- Animated statistics updates with number transitions

### 2.4 Program Chair - Card-Based Interface
**Shared Endpoints with Director:**
- Same `director.php?action=get_dashboard_data` endpoint
- Same underlying data queries (`getAllFaculty`, `getAllClasses`, etc.)

**Additional Program Chair Endpoints:**
- `program.php` with `action=get_courses_and_classes`
- `program.php` with `action=assign_course_load`
- `program.php` with `action=get_curriculum_assignment_data`
- `program.php` with `action=remove_curriculum_assignment`
- `program.php` with `action=get_faculty_schedules`
- `program.php` with `action=validate_schedule`
- `program.php` with `action=get_course_curriculum`
- `program.php` with `action=get_validated_options`

**UI Characteristics:**
- **Interactive Cards:** Faculty, class, and course cards with action buttons
- **Workflow Focus:** Course assignment, schedule management, curriculum mapping
- **Contact Integration:** Direct email/phone actions from faculty cards
- **Schedule Modals:** Complex schedule view and course load assignment interfaces

**Polling Implementation:**
- Shares same 3-second polling with director
- Card-based updates instead of table row updates

### 2.5 Class Dashboard - Faculty Status Cards
**GET Endpoints:**
- `home.php?action=get_dashboard_data`
  - Returns class info, faculty data, and announcements
- `home.php?action=get_faculty_updates`
  - Returns updated faculty locations and course statuses
  - Includes pre-generated HTML for faculty cards

**Polling Implementation:**
- Faculty status polling every 3 seconds
- Live updates to faculty cards with status changes
- Custom event dispatching: `facultyUpdatesReceived`

### 2.6 Faculty Portal - Personal Dashboard
**POST Endpoints:**
- `faculty.php` with `action=mark_attendance`
- `faculty.php` with `action=update_location`
- `faculty.php` with `action=get_schedule`

**GET Endpoints:**
- `faculty.php?action=get_location_history`
- `faculty.php?action=get_status`

**Polling Implementation:**
- Location updates and schedule polling
- Heartbeat mechanism to maintain online status

## 3. SHARED UTILITY ENDPOINTS

### 3.1 Location Services (get_location.php)
**Primary Endpoint:** `assets/php/get_location.php`

**Supported Roles:**
- **Class Role:** Returns faculty locations for assigned class
- **Faculty Role:** Returns own location and status

**Response Format:**
```json
{
  "success": true,
  "faculty": [array of faculty with locations and status],
  "current_location": "string",
  "status": "available|busy|offline",
  "last_updated": "time_ago_string"
}
```

### 3.2 Statistics Service (get_statistics.php)
**Primary Endpoint:** `assets/php/get_statistics.php`

**Role-Specific Data:**
- **Campus Director:** Total counts across all entities
- **Program Chair:** Program-specific counts and metrics

**Response Format:**
```json
{
  "success": true,
  "data": {
    "total_faculty": number,
    "total_classes": number,
    "total_courses": number,
    "active_announcements": number,
    "available_faculty": number
  }
}
```

### 3.3 Admin Actions (handle_admin_actions.php)
**Primary Endpoint:** `assets/php/handle_admin_actions.php` (POST only)

**Supported Actions:**
- `add_faculty` - Add new faculty member
- `add_course` - Add new course
- `add_class` - Add new class
- `add_announcement` - Add new announcement
- `delete_faculty` - Delete faculty member
- `delete_course` - Delete course
- `delete_class` - Delete class
- `delete_announcement` - Delete announcement

## 4. POLLING ARCHITECTURE

### 4.1 Intelligent Polling System
The application implements a sophisticated polling system with:

**Visibility-Based Polling:**
- Only polls when relevant UI elements are visible
- Uses `IntersectionObserver` for efficient visibility detection
- Automatically pauses/resumes based on page visibility

**Role-Based Polling:**
- Director: Statistics + table data polling
- Program Chair: Statistics + table data polling (shared with director)
- Class: Faculty location and status polling
- Faculty: Schedule and location polling

**Error Handling:**
- Exponential backoff on polling failures
- Maximum 30-second intervals on errors
- Automatic recovery after 5 minutes

### 4.2 Polling Intervals
**Default Intervals:**
- Statistics: 3 seconds
- Location updates: 3 seconds
- Table updates: 3 seconds
- Announcements: 3 seconds
- Session heartbeat: 15 seconds

**Adaptive Intervals:**
- Doubles on error (max 30s)
- Resets to normal after 5 minutes
- Pauses when page/elements not visible

## 5. FRONTEND POLLING IMPLEMENTATIONS

### 5.1 Director Dashboard (director.js)
- No explicit polling code found
- Relies on shared `LivePollingManager`
- Updates statistics with animated number transitions

### 5.2 Program Chair Dashboard (program.js)
- No explicit polling code found
- Relies on shared `LivePollingManager`
- Card-based interface updates

### 5.3 Class Dashboard (home.js)
- Custom event listener for `facultyUpdatesReceived`
- Real-time faculty card updates
- Dynamic course status updates based on time

### 5.4 Faculty Portal (faculty.js)
- No explicit polling code found
- Relies on backend heartbeat and shared polling
- Location update mechanisms

## 6. SHARED QUERY OPTIMIZATION PATTERNS

### 6.1 Backend Query Reuse Strategy
The application demonstrates excellent architectural design by reusing core database queries across different user interfaces:

#### Single Source of Truth Functions
```php
// Used by Director (tables), Program Chair (cards), and Class (status cards)
function getAllFaculty($pdo) { /* Same query, different presentations */ }
function getAllClasses($pdo) { /* Same query, different presentations */ }  
function getAllCourses($pdo) { /* Same query, different presentations */ }
function getAllAnnouncements($pdo) { /* Same query, different presentations */ }
```

#### Presentation Layer Separation
**Director Frontend (director.js):**
```javascript
// Receives faculty data -> Renders as searchable/sortable table
addNewRowToTable('faculty', data);
// Focus: Administrative operations, bulk actions, detailed views
```

**Program Chair Frontend (program.js):**
```javascript  
// Receives same faculty data -> Renders as interactive cards
// Focus: Workflow actions, contact integration, schedule management
generateScheduleView(facultyId, 'schedule');
```

**Class Frontend (home.js):**
```javascript
// Receives filtered faculty data -> Renders as status-focused cards  
updateFacultyCards(facultyData, facultyCourses);
// Focus: Real-time status, current courses, location tracking
```

### 6.2 Polling Efficiency Through Shared Endpoints
The `LivePollingManager` leverages this shared backend pattern:

```javascript
// Same endpoint serves different roles
const endpoint = userRole === 'campus_director' ? 
    'director.php?action=get_dashboard_data' :
    'director.php?action=get_dashboard_data'; // Program chair uses same!

// Frontend determines how to present the data
if (userRole === 'campus_director') {
    // Update table rows
    updateTableData(data);
} else if (userRole === 'program_chair') {
    // Update cards
    updateCardData(data);  
}
```

### 6.3 Benefits of Shared Backend Pattern

**Database Efficiency:**
- Single optimized query serves multiple UI patterns
- Consistent data structure across all presentations
- Reduced server load through query reuse

**Maintenance Benefits:**
- One query to optimize for all user roles
- Single point of truth for business logic
- Consistent data validation across interfaces

**Development Efficiency:**
- Backend developers focus on optimized queries
- Frontend developers focus on role-specific UX
- Clear separation of concerns

## 7. DATA FLOW PATTERNS

### 6.1 Real-Time Faculty Tracking
1. Faculty updates location via `faculty.php?action=update_location`
2. Live polling fetches updates via `get_location.php`
3. Class dashboards receive updates via `home.php?action=get_faculty_updates`
4. UI updates faculty cards with new status/location

### 6.2 Dashboard Statistics
1. Admin actions trigger data changes via `handle_admin_actions.php`
2. Statistics polling fetches updated counts via `get_statistics.php`
3. Dashboard cards update with animated number transitions
4. Error handling maintains data consistency

### 6.3 Schedule Management
1. Program chairs assign courses via `program.php?action=assign_course_load`
2. Validation occurs via `program.php?action=validate_schedule`
3. Schedule updates propagate via polling to all relevant dashboards
4. Real-time conflict detection and resolution

## 8. PRESENTATION LAYER EXAMPLES

### 8.1 Faculty Data - Three Different Interfaces

**Same Backend Data:**
```php
// getAllFaculty() returns:
{
    "faculty_id": 1,
    "faculty_name": "Dr. John Smith", 
    "current_location": "Room 101",
    "status": "available",
    "contact_email": "john@university.edu",
    "office_hours": "MWF 2-4 PM"
}
```

**Director Presentation (Table):**
```html
<tr>
    <td>Dr. John Smith</td>
    <td>EMP-0001</td>
    <td>Computer Science</td>
    <td><span class="status-badge status-available">Available</span></td>
    <td>Room 101</td>
    <td>john@university.edu</td>
    <td><button class="delete-btn">Delete</button></td>
</tr>
```

**Program Chair Presentation (Card):**
```html
<div class="faculty-card">
    <div class="faculty-name">Dr. John Smith</div>
    <div class="status-dot status-available">Available - Room 101</div>
    <div class="faculty-actions">
        <button onclick="contactFaculty('john@university.edu')">Email</button>
        <button onclick="viewSchedule(1)">Schedule</button>
        <button onclick="viewCourseLoad(1)">Course Load</button>
    </div>
</div>
```

**Class Presentation (Status Card):**
```html
<div class="faculty-card" data-faculty-id="1">
    <h3>Dr. John Smith</h3>
    <div class="status-badge status-available">Available</div>
    <div class="current-location">Room 101</div>
    <div class="courses-section">
        <!-- Today's courses for this faculty -->
    </div>
</div>
```

### 8.2 Course Data - Adaptive Presentations

**Director:** Course management table with units, scheduling count, delete actions
**Program Chair:** Course assignment cards with curriculum mapping and faculty assignment workflows  
**Class:** Course status cards showing current/upcoming/completed states

## 9. SECURITY AND VALIDATION

### 9.1 Session Validation
All endpoints implement role-based access control:
- `validateUserSession()` function checks user roles
- Session heartbeat maintains active sessions
- Automatic logout on session expiration

### 9.2 Input Validation
- `validateInput()` function sanitizes all inputs
- SQL injection prevention via prepared statements
- JSON response standardization via `sendJsonResponse()`

## 10. PERFORMANCE OPTIMIZATIONS

### 10.1 Shared Query Efficiency
- Visibility-based polling reduces unnecessary requests
- Exponential backoff prevents server overload
- Batched updates minimize UI redraws

### 10.2 Polling Efficiency
- Visibility-based polling reduces unnecessary requests
- Exponential backoff prevents server overload
- Batched updates minimize UI redraws
- Same endpoint serves multiple role presentations

### 10.3 Data Caching
- Frontend caches polling responses
- Conditional updates only when data changes
- Optimized SQL queries with proper indexing

## 11. ARCHITECTURAL STRENGTHS

### 11.1 Shared Backend Pattern Benefits
✅ **Query Optimization:** Single optimized SQL query serves multiple UI patterns
✅ **Data Consistency:** All roles see the same underlying data, presented differently  
✅ **Maintenance Efficiency:** One backend function to maintain for all presentations
✅ **Role-Specific UX:** Each role gets interface optimized for their workflow
✅ **Polling Efficiency:** Same endpoints serve multiple roles via shared LivePollingManager
✅ **Development Separation:** Backend focuses on data, frontend focuses on presentation

### 11.2 Examples of Excellent Code Reuse
- `getAllFaculty()` serves director tables, program chair cards, and class status displays
- `director.php?action=get_dashboard_data` serves both director and program chair roles
- Polling system adapts presentation based on user role without duplicating requests
- Statistics endpoint provides same data to different dashboard layouts

## 12. MISSING ENDPOINTS ANALYSIS

Based on the comprehensive review, the application has a well-structured endpoint system with:
- ✅ Complete CRUD operations for all entities
- ✅ Real-time polling for live updates
- ✅ Role-based access control
- ✅ Error handling and recovery
- ✅ Performance optimizations

**No significant missing endpoints identified.** The system appears comprehensive for the faculty tracking use case.