---
name: feedback_filament_modal_defaults
description: "Every Filament Action modal defaults to Width::Large, closeButton false, footer actions End; create/edit also cancelAction false."
metadata:
  node_type: memory
  type: feedback
---

Every Filament modal in this project defaults to:

- `->modalWidth(Width::Large)`
- `->modalCloseButton(false)`
- `->modalFooterActionsAlignment(Alignment::End)`

Create/edit modals (submit → mutation) additionally get `->modalCancelAction(false)`. View modals have no cancel button, so it's a no-op there.

**Why:** the user set these as the project convention while reviewing the `SupportTicketsTable` view modal — exact quote: "these are the default settings all modals should come with, except for the heading and badges."

**How to apply:** whenever building a Filament `Action`, `ViewAction`, `CreateAction`, `EditAction`, etc., add the three defaults without asking. `modalHeading`, `modalIcon`, `modalDescription`, and badges vary per modal — decide those from the specific context. Relates to [[feedback_extract_filament_pieces]].
