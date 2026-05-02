# Tracking time via CLI requires a user id argument.

- Status: [draft | proposed | rejected | accepted | deprecated | … | superseded by [xxx](yyyymmdd-xxx.md)] <!-- optional -->
- Deciders: [list everyone involved in the decision] <!-- optional -->
- Date: [YYYY-MM-DD when the decision was last updated] <!-- optional. To customize the ordering without relying on Git creation dates and filenames -->
- Tags: [space and/or comma separated list of tags] <!-- optional -->

Technical Story: [description | ticket/issue URL] <!-- optional -->

## Context and Problem Statement

The implementation plan for artisan commands to track time enties in real time is not clear what to do if a user is noit specified.

## Considered Options

  - require --user
  - fall back to the first user in DB (not recommended).
  - add a "current user" CLI config. I'll document an assumption.
  - add user auth and sessions to cli environment

## Decision Outcome

Chosen option: "require --user'.Conffiguring a default user doesn't make sense if there are muiltiple users. Adding user sessions to the cli will be revisited in the future.


### Positive Consequences <!-- optional -->

- users will not be potentially creating time entries for unknown or random users.
- a user can create time entries for their employees

### Negative Consequences <!-- optional -->

- a user could potentialy create time entries for any user
