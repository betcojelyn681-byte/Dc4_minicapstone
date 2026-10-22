# Setup and testing

## Local setup
- Start Laragon MySQL; create `student_class_scheduling`.
- Backend: `copy .env.example .env`, `composer install`, `php artisan key:generate`, update DB values, `php artisan migrate --seed`, `php artisan serve`.
- Frontend: `cd frontend`, `copy .env.example .env`, `npm install`, `npm run dev`.

## Checks before submission
- [ ] `npm run build`
- [ ] `php artisan route:list`
- [ ] `php artisan test`
- [ ] Admin/student/teacher role permissions verified using actual API requests
- [ ] Create/edit/delete each academic resource
- [ ] Conflicting room, section, instructor schedule rejected
- [ ] Adjacent time slots accepted
- [ ] Student sees only published classes for own section
- [ ] Teacher sees only their assigned classes
- [ ] Search, filtering, pagination, empty states and print verified
- [ ] Responsive at mobile, tablet and desktop widths

The project has not been tested in your particular Laragon installation. Record actual results, not assumptions.
