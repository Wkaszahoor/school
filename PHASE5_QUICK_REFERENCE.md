# Phase 5-6 Quick Reference Guide

## File Count Summary
- **26 Total Files Created**
  - 1 Type definition file
  - 13 Shared components
  - 7 Principal pages
  - 5 Teacher pages
  - 1 Summary document (this file)

---

## Component Tree

### Shared Components (All Reusable)

```
StatusBadge
├── Props: status, type, size
└── Used in: All pages with status display

EnrollmentProgressBar
├── Props: enrollment (with progress_percentage, status)
└── Used in: Course cards, enrollment displays

CertificateCard
├── Props: certification
└── Used in: Certifications pages

CourseCard
├── Props: course, actions, canEnroll, onEnroll
└── Used in: Course listings

ResourceCard
├── Props: resource, canEdit, onDelete, onDownload
└── Used in: Resource library

SubmissionCard
├── Props: submission, onDownload, onGrade, onFeedback
└── Used in: Submission review pages

PBLRubricDisplay
├── Props: rubric, isEditable, scores, onScoreChange
└── Used in: Grading pages
```

---

## Page Hierarchy

### Principal Routes Structure
```
/principal/professional-development/
├── training-courses/
│   ├── index (list all courses)
│   ├── create (new course form)
│   ├── {id}/edit (edit course)
│   ├── {id}/show (course enrollments)
│
├── pbl-assignments/
│   ├── index (list all assignments)
│   ├── create (new assignment form)
│   ├── {id}/edit (edit assignment)
│   ├── {id}/show (assignment details)
│   └── {id}/submissions (all submissions)
│
└── certifications/
    ├── index (list certificates)
    ├── {id}/show (certificate details)
    └── report (analytics dashboard)
```

### Teacher Routes Structure
```
/teacher/professional-development/
├── training-courses/
│   ├── index (browse & enroll)
│   └── {id}/show (course details)
│
├── pbl-assignments/
│   ├── index (my assignments)
│   ├── create (new assignment)
│   ├── {id}/edit (edit assignment)
│   └── {id}/show (assignment details)
│
├── resources/
│   ├── index (library + my resources)
│   ├── create (upload resource)
│   ├── {id}/edit (edit resource)
│   └── {id}/show (resource details)
│
└── my-certifications/
    └── index (my certificates)
```

---

## Component Import Examples

### Import Types
```typescript
import {
  TrainingCourse,
  CourseEnrollment,
  Certification,
  TeachingResource,
  PBLAssignment
} from '@/types/professional-development';
```

### Import Components
```typescript
import StatusBadge from '@/Components/ProfessionalDevelopment/StatusBadge';
import EnrollmentProgressBar from '@/Components/ProfessionalDevelopment/EnrollmentProgressBar';
import CertificateCard from '@/Components/ProfessionalDevelopment/CertificateCard';
import CourseCard from '@/Components/ProfessionalDevelopment/CourseCard';
import ResourceCard from '@/Components/ProfessionalDevelopment/ResourceCard';
import SubmissionCard from '@/Components/ProfessionalDevelopment/SubmissionCard';
import PBLRubricDisplay from '@/Components/ProfessionalDevelopment/PBLRubricDisplay';
```

---

## Common Patterns

### Status Badge Usage
```typescript
<StatusBadge status="published" type="course" size="md" />
<StatusBadge status="active" type="cert" size="sm" />
<StatusBadge status="submitted" type="assignment" size="md" />
```

### Progress Bar Usage
```typescript
<EnrollmentProgressBar enrollment={enrollment} />
```

### Card Component Usage
```typescript
<CourseCard course={course} canEnroll={true} onEnroll={handleEnroll} />
<CertificateCard certification={cert} />
<ResourceCard resource={resource} canEdit={true} onDelete={handleDelete} />
```

### Pagination Usage
```typescript
<Pagination
  currentPage={data.current_page}
  lastPage={data.last_page}
  total={data.total}
  perPage={data.per_page}
/>
```

### Modal Usage
```typescript
<Modal
  open={isOpen}
  onClose={() => setIsOpen(false)}
  title="Modal Title"
>
  {/* Content */}
</Modal>
```

---

## Color Coding Reference

### Status Badges
| Status | Color | Class |
|--------|-------|-------|
| Published | Green | `bg-green-100 text-green-800` |
| Draft | Gray | `bg-gray-100 text-gray-800` |
| Archived | Gray | `bg-gray-100 text-gray-600` |
| Active (Cert) | Green | `bg-green-100 text-green-800` |
| Expired | Red | `bg-red-100 text-red-800` |
| Revoked | Red | `bg-red-100 text-red-800` |
| In Progress | Yellow | `bg-yellow-100 text-yellow-800` |
| Completed | Green | `bg-green-100 text-green-800` |

### Border/Background Colors
| Purpose | Color |
|---------|-------|
| Expired Cert | Red border & red-50 bg |
| Expiring Soon | Yellow border & yellow-50 bg |
| Active Cert | Green border & green-50 bg |
| Primary Buttons | Blue-500 |
| Success Buttons | Green-500 |
| Danger Buttons | Red-500 |
| Warning Buttons | Yellow-500 |

---

## Responsive Grid Breakpoints

### TailwindCSS Classes Used
```
Mobile (default):     1 column
sm (≥640px):          1-2 columns
md (≥768px):          2 columns
lg (≥1024px):         3 columns
xl (≥1280px):         4 columns
```

### Grid Examples
```typescript
// 3-column responsive grid
<div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

// Table responsive
<div className="overflow-x-auto">
  <table className="w-full text-sm">
```

---

## State Management Patterns

### Local State with Hooks
```typescript
const [isOpen, setIsOpen] = useState(false);
const [filters, setFilters] = useState({ status: '', level: '' });
const [selectedItem, setSelectedItem] = useState(null);
```

### Form Submission with Inertia
```typescript
const handleDelete = (id: number) => {
  if (confirm('Are you sure?')) {
    router.delete(route('resource.destroy', id), {
      onSuccess: () => {
        // Handle success
      },
    });
  }
};
```

### Flash Messages
```typescript
const { flash } = usePage().props;

{flash?.success && (
  <div className="mb-6 p-4 bg-green-100 text-green-700 rounded-lg">
    {flash.success}
  </div>
)}
```

---

## Form Validation Pattern

```typescript
// Display errors from props
interface Props extends PageProps {
  errors?: Record<string, string>;
}

// In form
{errors?.field_name && (
  <p className="text-red-600 text-sm mt-1">{errors.field_name}</p>
)}
```

---

## Navigation Examples

### Using Link Component
```typescript
<Link
  href={route('principal.training-courses.show', course.id)}
  className="text-blue-500 hover:text-blue-600"
>
  View Details
</Link>
```

### Using Router
```typescript
router.get(route('principal.certifications.index'), {
  type: filterValue,
}, { preserveScroll: true });
```

---

## Modal Dialog Pattern

```typescript
const [open, setOpen] = useState(false);

<Modal
  open={open}
  onClose={() => setOpen(false)}
  title="Confirm Action"
>
  <div className="text-center py-4">
    <p className="text-gray-900 font-medium mb-4">Are you sure?</p>
    <div className="flex gap-2 justify-center">
      <button
        onClick={() => setOpen(false)}
        className="px-4 py-2 bg-gray-300 rounded-lg"
      >
        Cancel
      </button>
      <button
        onClick={handleConfirm}
        className="px-4 py-2 bg-blue-500 text-white rounded-lg"
      >
        Confirm
      </button>
    </div>
  </div>
</Modal>
```

---

## Pagination Pattern

```typescript
interface Data {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

<Pagination
  currentPage={data.current_page}
  lastPage={data.last_page}
  total={data.total}
  perPage={data.per_page}
/>
```

---

## File Organization

### Avoid Creating Duplicate Components
Each component has a single purpose. Before creating a new component, check if a similar one exists:

- `StatusBadge` - For all status displays
- `CourseCard` - For course previews
- `CertificateCard` - For certificate displays
- `ResourceCard` - For resource previews
- `SubmissionCard` - For submission displays

### Maintain Consistent Naming
- Components: PascalCase (e.g., StatusBadge)
- Files: PascalCase.tsx (e.g., StatusBadge.tsx)
- Props interfaces: ComponentNameProps (e.g., StatusBadgeProps)
- Routes: kebab-case (e.g., training-courses.index)

---

## Testing Checklist

Before deployment, verify:

- [ ] All TypeScript types compile without errors
- [ ] All imports resolve correctly
- [ ] Components render without console errors
- [ ] Responsive design works on mobile/tablet/desktop
- [ ] All buttons and links have appropriate styling
- [ ] Modal dialogs open and close properly
- [ ] Flash messages display correctly
- [ ] Pagination links work correctly
- [ ] Filters update page content
- [ ] Confirmation modals prevent accidental actions
- [ ] Color contrast meets WCAG standards

---

## Next Steps (Phase 7)

### Form Pages to Create
- [ ] TrainingCourses/Create.tsx - Course creation form
- [ ] TrainingCourses/Edit.tsx - Course editing
- [ ] PBLAssignments/Create.tsx - Assignment creation
- [ ] PBLAssignments/Edit.tsx - Assignment editing
- [ ] Resources/Create.tsx - Resource upload form
- [ ] Resources/Edit.tsx - Resource editing

### Detail Pages to Create
- [ ] PBLAssignments/Show.tsx - Full assignment view
- [ ] PBLAssignments/Submissions.tsx - All submissions
- [ ] Resources/Show.tsx - Resource detail view
- [ ] TrainingCourses/Progress.tsx - Progress tracking

### Components to Add
- [ ] FileUploadInput - File selection with validation
- [ ] RichTextEditor - For descriptions
- [ ] DateRangePicker - For date selection
- [ ] FileProgressBar - For upload progress

---

## Support & Reference

**Documentation:**
- PHASE5_COMPLETION_SUMMARY.md - Full documentation
- professional-development.d.ts - All type definitions
- Each component includes JSDoc comments

**File Locations:**
- Components: `/resources/js/Components/ProfessionalDevelopment/`
- Pages: `/resources/js/Pages/{Principal|Teacher}/ProfessionalDevelopment/`
- Types: `/resources/js/types/professional-development.d.ts`

---

**Created:** March 7, 2026
**Status:** Phase 5-6 Complete
**Next:** Phase 7 - Form Pages & Backend Integration
