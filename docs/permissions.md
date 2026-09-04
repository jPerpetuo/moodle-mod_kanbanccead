# Permissions and Groups

## Capability reference

All capabilities below use module context unless noted otherwise.

| Capability | Default intent |
| --- | --- |
| `mod/kanbanccead:addinstance` | Create the activity; course context. |
| `mod/kanbanccead:view` | Open the activity. |
| `mod/kanbanccead:addcard` | Create cards and duplicate cards. |
| `mod/kanbanccead:assignself` | Assign or unassign oneself. |
| `mod/kanbanccead:assignothers` | Assign or unassign other users. |
| `mod/kanbanccead:manageassignedcards` | Manage cards assigned to the current user. |
| `mod/kanbanccead:manageallcards` | Manage any card in an accessible board. |
| `mod/kanbanccead:managecolumns` | Add, edit, move, lock, or delete columns. |
| `mod/kanbanccead:viewhistory` | Read board history. |
| `mod/kanbanccead:viewallboards` | Navigate all eligible boards in the activity. |
| `mod/kanbanccead:editallboards` | Edit all eligible boards in the activity. |
| `mod/kanbanccead:manageboard` | Manage board-level actions, including templates and board deletion. |

Deprecated capabilities are mapped in [db/access.php](../db/access.php); new role definitions should use their replacements.

## Default role model

Students can view, add cards, assign themselves, and manage cards assigned to them. Editing teachers and managers receive the broader board, column, history, and all-board capabilities. Guests are prevented from modifying content.

Moodle site administrators can override any capability. This table describes defaults, not a hard authorization policy for every installation.

## Recommended monitor role

For a monitor who must view all group boards without being enrolled in every group, grant `mod/kanbanccead:viewallboards`. Grant `mod/kanbanccead:editallboards` only if the monitor must edit cards in every board. Add `mod/kanbanccead:manageallcards` where card-level edits are required.

Do not grant broad course group capabilities merely to solve Kanban navigation. The all-boards capabilities are intentionally scoped to this activity.

## Group security invariants

1. A student must not obtain another group's board merely by changing a board identifier in the URL or AJAX request.
2. Server-side board resolution must enforce membership unless an all-boards capability applies.
3. The group selector is a navigation affordance, not a substitute for authorization.
4. Changing group membership affects which group boards are available to the user; it does not copy cards or change board ownership.

