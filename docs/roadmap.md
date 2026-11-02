# NEXORA — Development Roadmap

This roadmap describes the intended implementation order. A phase is complete only after its code, validation, and relevant documentation are present.

## Phase 1 — Repository and environment
- [x] Create the public GitHub repository
- [x] Add the initial project overview
- [x] Add Git ignore rules
- [ ] Document local PHP and MySQL setup
- [ ] Add safe environment configuration examples

## Phase 2 — Database
- [ ] Design and review the relational schema
- [ ] Add versioned SQL migrations
- [ ] Add development seed data
- [ ] Verify relationships, indexes, and constraints

## Phase 3 — Backend foundation
- [ ] Configure Composer and autoloading
- [ ] Establish application configuration
- [ ] Create the API entry point and routing
- [ ] Implement consistent JSON responses and error handling
- [ ] Add PDO connection management

## Phase 4 — Authentication and authorization
- [ ] Registration and login
- [ ] Secure password hashing and verification
- [ ] Session/token lifecycle
- [ ] Workspace-level role and permission checks
- [ ] Rate limiting and input validation

## Phase 5 — Work management
- [ ] Workspaces and members
- [ ] Projects
- [ ] Tasks, assignments, priorities, and due dates
- [ ] Labels and comments
- [ ] Activity history

## Phase 6 — Frontend
- [ ] Responsive application layout
- [ ] Authentication screens
- [ ] Workspace and project views
- [ ] Task board and task details
- [ ] Dashboard and reporting views

## Phase 7 — Quality and release
- [ ] Integrate frontend with the API
- [ ] Add automated tests
- [ ] Review security and authorization boundaries
- [ ] Document endpoints and installation
- [ ] Capture genuine screenshots of the working application
