# Architecture diagrams

When a change alters module boundaries or responsibilities, APIs/events between
components, integrations, persistence, queues, authentication, or a depicted
business flow, inspect and update every affected `docs/architecture/*.puml` in
the same pull request. If the change does not alter a diagram, briefly explain
why in the PR description or delivery summary. Treat `.puml` files as the
source of truth; regenerate versioned SVGs and review them visually. See the
[architecture index](docs/architecture/overview.md) and
[diagram workflow](docs/architecture/diagrams/README.md).
