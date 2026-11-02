# NEXORA

**Project & Team Management Platform**

NEXORA is a portfolio project focused on organizing workspaces, projects, teams, and tasks through a web application backed by a REST API.

> **Project status:** Initial setup / in development. Features listed in the roadmap are planned and should not be considered implemented until their code and tests are added.

## Planned technology stack

- **Backend:** PHP 8.3+, REST API, PDO, MVC-inspired organization
- **Database:** MySQL 8+
- **Frontend:** HTML5, CSS3, JavaScript, Bootstrap 5
- **Tools:** Composer, Git, GitHub, Postman, PHPUnit

## Planned core modules

- User registration, authentication, profiles, and role-based permissions
- Workspaces and membership management
- Projects and task management
- Task labels, comments, and activity history
- Dashboard and project progress reporting

## Repository structure

```text
NEXORA/
├── backend/       # PHP REST API
├── frontend/      # Web interface
├── docs/          # Technical documentation
└── README.md
```

The structure will grow as each module is implemented.

## Development principles

- Validate and authorize every sensitive operation on the server.
- Use PDO prepared statements for database access.
- Store passwords with PHP's password hashing functions.
- Keep credentials and secrets outside version control.
- Document API behavior and test important business rules.
- Mark mockups and unfinished features clearly.

## Getting started

The installation guide will be added as the local development environment and backend configuration are finalized. Do not place production credentials in this repository.

## Roadmap

See [docs/roadmap.md](docs/roadmap.md) for the planned implementation phases.

## License

This project is intended to be released under the MIT License. The license file will be added with the initial source-code release.
