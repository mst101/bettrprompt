# Vue/TypeScript Refactoring Checklist

## Phase 1: High-Impact Foundation (4-6 hours)

### Component 1: FormField.vue
- [ ] Create `/resources/js/Components/FormField.vue`
- [ ] Define TypeScript props interface
- [ ] Support text, email, password, number, textarea types
- [ ] Handle error display
- [ ] Add autofocus, required, placeholder support
- [ ] Test with at least 3 auth pages
- [ ] Refactor files:
  - [ ] `Pages/Auth/Login.vue` (2 fields)
  - [ ] `Pages/Auth/Register.vue` (4 fields)
  - [ ] `Pages/Auth/ForgotPassword.vue` (1 field)
  - [ ] `Pages/Auth/ResetPassword.vue` (3 fields)
  - [ ] `Pages/Auth/ConfirmPassword.vue` (1 field)
  - [ ] `Pages/Profile/Partials/UpdateProfileInformationForm.vue` (2 fields)
  - [ ] `Pages/Profile/Partials/UpdatePasswordForm.vue` (3 fields)
  - [ ] `Pages/Profile/Partials/DeleteUserForm.vue` (1 field)

### Component 2: Card.vue
- [ ] Create `/resources/js/Components/Card.vue`
- [ ] Define padding variants (sm, md, lg)
- [ ] Define shadow variants
- [ ] Add margin-bottom toggle
- [ ] Add overflow hidden option
- [ ] Test responsive design
- [ ] Refactor files:
  - [ ] `Pages/Profile/Edit.vue` (3 cards)
  - [ ] `Pages/PromptOptimizer/Show.vue` (6+ cards)
  - [ ] `Pages/PromptOptimizer/Index.vue` (1 card)
  - [ ] `Pages/Dashboard.vue` (1 card)

### Component 3: LoadingSpinner.vue
- [ ] Create `/resources/js/Components/LoadingSpinner.vue`
- [ ] Support size variants (sm, md, lg)
- [ ] Support color variants (indigo, white, gray)
- [ ] Add optional label text
- [ ] Create flexible layout for icon + label
- [ ] Refactor files:
  - [ ] `Pages/PromptOptimizer/Show.vue` (3 instances)

### Composable 1: useStatusBadge.ts
- [ ] Create `/resources/js/composables/useStatusBadge.ts`
- [ ] Export `getStatusClass(status)` function
- [ ] Export `getStatusLabel(status)` function
- [ ] Add support for: completed, processing, failed, pending
- [ ] Create unit tests
- [ ] Refactor files:
  - [ ] `Pages/PromptOptimizer/Show.vue`
  - [ ] `Pages/PromptOptimizer/History.vue`

### Testing Phase 1:
- [ ] Test FormField with multiple input types
- [ ] Test Card at different breakpoints
- [ ] Test LoadingSpinner appearance
- [ ] Verify status badge colours match design
- [ ] Run npm run build to check for errors
- [ ] Test in browser on mobile and desktop

---

## Phase 2: Form Consistency (3-4 hours)

### Component 4: FormSection.vue
- [ ] Create `/resources/js/Components/FormSection.vue`
- [ ] Wrap section + header + form pattern
- [ ] Support title and description props
- [ ] Add spacing customization
- [ ] Refactor files:
  - [ ] `Pages/Profile/Partials/UpdateProfileInformationForm.vue`
  - [ ] `Pages/Profile/Partials/UpdatePasswordForm.vue`
  - [ ] `Pages/Profile/Partials/DeleteUserForm.vue`

### Component 5: FormActions.vue
- [ ] Create `/resources/js/Components/FormActions.vue`
- [ ] Support submit label customization
- [ ] Handle disabled/loading state
- [ ] Include success message feedback
- [ ] Add Transition for smooth feedback
- [ ] Refactor files:
  - [ ] `Pages/Profile/Partials/UpdateProfileInformationForm.vue`
  - [ ] `Pages/Profile/Partials/UpdatePasswordForm.vue`

### Component 6: SectionHeader.vue
- [ ] Create `/resources/js/Components/SectionHeader.vue`
- [ ] Support title and description
- [ ] Add size variants (sm, md, lg)
- [ ] Test styling hierarchy

### Composable 2: useFormStatus.ts
- [ ] Create `/resources/js/composables/useFormStatus.ts`
- [ ] Manage recentlySuccessful state
- [ ] Export `handleSuccess()` function
- [ ] Auto-hide success message after 2 seconds
- [ ] Refactor files:
  - [ ] `Pages/Profile/Partials/UpdateProfileInformationForm.vue`
  - [ ] `Pages/Profile/Partials/UpdatePasswordForm.vue`

### Testing Phase 2:
- [ ] Test form submission flow
- [ ] Test success message display
- [ ] Verify form field validation still works
- [ ] Test on mobile layout
- [ ] Ensure accessibility is maintained

---

## Phase 3: Advanced Features (4-5 hours)

### Component 7: CollapsibleItem.vue
- [ ] Create `/resources/js/Components/CollapsibleItem.vue`
- [ ] Support title and content slots
- [ ] Add optional number display
- [ ] Manage open/closed state
- [ ] Smooth transitions
- [ ] Refactor files:
  - [ ] `Pages/PromptOptimizer/Show.vue` (questions section)

### Component 8: Accordion.vue (optional wrapper)
- [ ] Create `/resources/js/Components/Accordion.vue`
- [ ] Support single or multiple open items
- [ ] Coordinate state across children
- [ ] Future use for FAQs, settings

### Component 9: EmptyState.vue
- [ ] Create `/resources/js/Components/EmptyState.vue`
- [ ] Support custom message
- [ ] Add optional action button/link
- [ ] Icon support
- [ ] Refactor files:
  - [ ] `Pages/PromptOptimizer/History.vue`

### Component 10: ProgressBar.vue
- [ ] Create `/resources/js/Components/ProgressBar.vue`
- [ ] Calculate percentage from current/total
- [ ] Show label option
- [ ] Show percentage option
- [ ] Smooth transitions
- [ ] Refactor files:
  - [ ] `Pages/PromptOptimizer/Show.vue` (progress bar)

### Composable 3: useWorkflowStage.ts
- [ ] Create `/resources/js/composables/useWorkflowStage.ts`
- [ ] Export stage label mapping
- [ ] Export stage colour/styling
- [ ] Support all workflow stages

### Testing Phase 3:
- [ ] Test accordion expand/collapse
- [ ] Test empty state message
- [ ] Test progress bar animation
- [ ] Verify all stage labels display correctly

---

## Phase 4: Polish & Documentation (2 hours)

### Code Quality:
- [ ] Add JSDoc comments to all components
- [ ] Add TypeScript types to all props/returns
- [ ] Create type definitions file for common types
- [ ] Run linting/formatting (npm run lint)

### Documentation:
- [ ] Create component storybook stories (optional)
- [ ] Write README for each new component
- [ ] Add usage examples in comments
- [ ] Document prop interfaces

### Final Testing:
- [ ] Full regression test all pages
- [ ] Mobile responsive testing
- [ ] Accessibility audit
- [ ] Performance check
- [ ] Browser compatibility check

### Cleanup:
- [ ] Remove old duplicate code
- [ ] Verify no unused imports/variables
- [ ] Update type definitions
- [ ] Commit changes with clear messages

---

## Summary Stats

### Files to Create:
- 10 new Vue components
- 3 new composables
- 1 optional component (Accordion wrapper)

### Files to Refactor:
- 8 page/form files
- 1 major feature page (Show.vue)

### Estimated Total Time: 13-17 hours
- Phase 1: 4-6 hours (immediate high impact)
- Phase 2: 3-4 hours (form consistency)
- Phase 3: 4-5 hours (advanced features)
- Phase 4: 2 hours (polish)

### Expected Results:
- 30-40% reduction in duplicate code
- ~220 lines of code saved
- Single source of truth for 13+ patterns
- Improved consistency across application
- Better foundation for future features

---

## Success Criteria

- [ ] All form pages use FormField component
- [ ] All card containers use Card component
- [ ] All spinners use LoadingSpinner component
- [ ] All status badges use consistent styling
- [ ] All form sections follow consistent pattern
- [ ] Build succeeds with no errors/warnings
- [ ] All pages render correctly after refactoring
- [ ] No regression in functionality
- [ ] Mobile layout still responsive
- [ ] TypeScript types fully integrated

