# Flag architecture limitations upfront

When the user picks between architectural options (A vs B vs C), surface KNOWN vendor/framework limitations at decision time — not later when an error surfaces.

**Concrete incident:** User picked "service account per project" for Google Calendar multi-tenancy. I didn't flag that service accounts cannot invite attendees without Domain-Wide Delegation. Days later, `403 forbiddenForServiceAccounts` on first real event, requiring a Workspace admin setup they hadn't accounted for. User was livid — "why didnt you fucking tell me that you moron before we fucking started doing this".

**How to apply:** When enumerating options in a plan, for each option list its hard constraints (vendor restrictions, requires admin setup, doesn't work with X). Better to spend 30 seconds warning "option A requires Workspace admin access to enable DWD" than to build the wrong architecture.
