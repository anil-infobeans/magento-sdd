<!--
Sync Impact Report
===================
Version change: [TEMPLATE] → 1.0.0 (initial ratification)
Modified principles: N/A (first adoption; no prior versioned constitution existed — the
  file previously contained only the unfilled template scaffold)
Added sections:
  - Core Principles I-XII (Code Quality and Architecture, Code Maintainability,
    PHP and PSR Compliance, Testing Standards, User Experience Consistency,
    Performance, Security and Data Handling, Magento Core Compatibility,
    Configuration and Deployment Safety, Version Control and Feature Branches,
    Quality Gates, Principle of Least Change)
  - Reference Standards
  - Acceptance Criteria
  - Governance
Removed sections: Generic template placeholders (5 example principles, 2 generic
  sections) replaced with Magento/Adobe Commerce-specific governance content.
Templates requiring alignment review:
  - .specify/templates/plan-template.md ⚠ pending manual review for Magento-specific
    Constitution Check gates (architecture, PSR, testing, UX, performance, security)
  - .specify/templates/spec-template.md ⚠ pending manual review for testability /
    UX-consistency language alignment
  - .specify/templates/tasks-template.md ⚠ pending manual review to ensure task
    categories reflect module/plugin/observer/test layering
  - .specify/templates/checklist-template.md ⚠ pending manual review
Follow-up TODOs:
  - TODO(RATIFICATION_DATE): Original historical adoption date is unknown; the
    ratification date below has been set to the date this constitution was first
    formally authored. Update if an earlier authoritative date is identified.
-->

# Magento 2.4.8 (Adobe Commerce) Constitution
<!-- Enterprise Edition codebase governed by this constitution: /var/www/html/Magento248 -->

## Core Principles

### I. Code Quality and Architecture
WHEN implementing or modifying Magento functionality, THE system SHALL follow Magento's
established architectural patterns, module structure, dependency injection, service
contracts, repositories, factories, observers/plugins, UI components, configuration
conventions, and extension mechanisms.

WHEN adding business logic, THE implementation SHALL follow separation of concerns and
SHALL avoid placing business logic directly in controllers, templates, blocks, or
database-specific code where a proper Magento service/domain abstraction is applicable.

WHEN introducing dependencies, THE implementation SHALL use Magento dependency injection
rather than directly instantiating injectable classes with `new`.

WHEN extending existing Magento behavior, THE implementation SHALL prefer supported
Magento extension mechanisms such as plugins, observers, preferences, service contracts,
and extension attributes, selecting the least intrusive mechanism appropriate to the
requirement.

WHEN writing PHP code, THE implementation SHALL comply with applicable PHP-FIG PSR
standards and Magento coding conventions.

WHEN a PSR requirement conflicts with an established Magento framework convention, THE
implementation SHALL follow the documented Magento convention while preserving the
intent of the applicable PSR standard where technically possible.

WHEN implementing a new module, THE module SHALL follow Magento's standard module
structure, naming conventions, registration mechanism, dependency declaration,
configuration conventions, and autoloading requirements.

THE implementation SHALL avoid unnecessary customization of Magento core code.

IF Magento core functionality must be changed, THEN the implementation SHALL use an
extension-safe mechanism whenever one exists.

*Rationale*: Magento/Adobe Commerce upgrades, patches, and third-party modules depend on
predictable extension points; deviating from these patterns creates upgrade risk and
unsupportable customizations.

### II. Code Maintainability
WHEN code is introduced or modified, THE implementation SHALL favor readable, cohesive,
loosely coupled, testable, and maintainable code over premature abstraction or
unnecessary complexity.

WHEN a class has multiple unrelated responsibilities, THE implementation SHALL separate
those responsibilities into appropriate classes or services.

WHEN duplicate business logic is identified, THE implementation SHALL consolidate it
into an appropriate reusable abstraction when doing so improves maintainability without
introducing unnecessary coupling.

THE implementation SHALL use meaningful class, method, variable, configuration, and
interface names.

THE implementation SHALL include comments only where they explain non-obvious business
rules, architectural decisions, or implementation constraints.

THE implementation SHALL NOT use comments to compensate for unclear code structure.

*Rationale*: Long-lived Magento codebases are maintained by many contributors over
multiple years; clarity and cohesion reduce the cost of future changes.

### III. PHP and PSR Compliance
WHEN PHP source code is created or modified, THE implementation SHALL follow applicable
PSR standards, including relevant recommendations for coding style, autoloading,
interfaces, HTTP handling, logging, and caching.

WHEN namespaces or autoloading are required, THE implementation SHALL follow
PSR-compatible namespace and autoloading conventions and the project's Composer
configuration.

WHEN exceptions are handled, THE implementation SHALL use appropriate exception types
and SHALL NOT silently suppress errors.

WHEN logging is required, THE implementation SHALL use Magento's supported logging
mechanisms and SHALL NOT expose sensitive information through logs.

THE implementation SHALL avoid deprecated PHP or Magento APIs unless there is a
documented compatibility requirement.

*Rationale*: PSR compliance (see Reference Standards) keeps the codebase interoperable
with the broader PHP ecosystem and Magento's own PSR-based foundation.

### IV. Testing Standards
WHEN new functionality is implemented, THE implementation SHALL include automated tests
appropriate to the affected layer.

WHEN business logic can be isolated, THE implementation SHALL provide unit tests
covering the relevant behavior, including normal, boundary, and failure scenarios.

WHEN Magento framework integration is involved, THE implementation SHALL provide
integration tests where unit tests alone cannot adequately validate framework behavior.

WHEN customer-facing functionality is changed, THE implementation SHALL include
appropriate functional or end-to-end coverage when the change affects a user workflow.

WHEN an existing defect is fixed, THE implementation SHALL add or update a regression
test demonstrating the defect and verifying the fix.

THE test suite SHALL remain deterministic and SHALL NOT depend on external services
unless the test explicitly requires an integration boundary.

WHEN code coverage is used as a quality metric, THE project SHALL prioritize meaningful
behavioral coverage rather than achieving coverage through trivial assertions.

A change SHALL NOT be considered complete until relevant automated tests pass.

*Rationale*: Automated tests at the correct layer (unit, integration, functional) are
the primary safeguard against regressions in a system with as many integration points
as Magento.

### V. User Experience Consistency
WHEN customer-facing functionality is introduced or modified, THE implementation SHALL
follow existing Magento storefront and Admin UI patterns.

WHEN UI components, forms, messages, buttons, navigation, validation, loading states, or
error handling are introduced, THE implementation SHALL maintain consistency with the
existing application UX.

WHEN validation fails, THE system SHALL provide clear, actionable, and
user-understandable feedback.

WHEN an operation succeeds or fails, THE system SHALL communicate the result using the
established Magento UI messaging and notification patterns.

THE implementation SHALL preserve accessibility, responsive behavior, localization, and
internationalization requirements applicable to the affected interface.

WHEN text is presented to users, THE implementation SHALL use Magento's supported
translation mechanisms rather than hard-coded user-facing strings where translation is
applicable.

WHEN an existing UX pattern can satisfy a requirement, THE implementation SHALL reuse
that pattern instead of introducing a new, inconsistent interaction model.

*Rationale*: Consistency across storefront and Admin reduces user confusion and support
burden, and preserves accessibility and localization guarantees customers rely on.

### VI. Performance
WHEN implementing functionality that executes during customer requests, THE
implementation SHALL minimize unnecessary database queries, network calls, object
creation, filesystem operations, and expensive computations.

WHEN data is repeatedly accessed, THE implementation SHALL use appropriate Magento
caching mechanisms where caching provides measurable benefit and does not compromise
correctness.

WHEN loading collections or large datasets, THE implementation SHALL avoid unnecessary
full collection loading and SHALL use appropriate filtering, pagination, batching, or
lazy-loading strategies.

WHEN database access is required, THE implementation SHALL avoid N+1 query patterns and
SHALL use efficient, Magento-compatible data access mechanisms.

WHEN an operation can execute asynchronously, THE implementation SHOULD use
Magento-supported asynchronous or queue-based processing when appropriate.

WHEN performance-sensitive code is modified, THE implementation SHALL consider execution
time, memory usage, database queries, cache behavior, and scalability.

THE implementation SHALL NOT introduce premature optimization that materially reduces
readability or maintainability without evidence of a performance need.

IF a performance regression is identified, THEN the implementation SHALL address the
root cause and SHALL add an appropriate regression test, benchmark, or monitoring
mechanism where practical.

*Rationale*: Storefront response time and Admin throughput directly affect conversion
and operational efficiency; caching and query discipline are load-bearing concerns in
Magento.

### VII. Security and Data Handling
WHEN processing user-controlled input, THE implementation SHALL validate, sanitize,
authorize, and safely handle the input according to Magento security practices and the
affected context.

WHEN accessing protected functionality, THE implementation SHALL enforce appropriate
authorization and access-control checks.

WHEN database queries are constructed, THE implementation SHALL use Magento-supported
mechanisms that prevent SQL injection.

THE implementation SHALL NOT expose credentials, secrets, personal data, tokens, or
other sensitive information in source code, logs, exceptions, or user-facing messages.

WHEN rendering user-controlled data, THE implementation SHALL use appropriate Magento
escaping and output-safety mechanisms.

*Rationale*: Magento/Adobe Commerce stores handle payment, PII, and admin credentials;
input handling and output escaping are non-negotiable to prevent injection and data
exposure.

### VIII. Magento Core Compatibility
WHEN implementing functionality that depends on Magento APIs, THE implementation SHALL
prefer public, documented APIs and extension points.

THE implementation SHALL avoid direct modification of Magento vendor/core files.

WHEN an internal or undocumented API is unavoidable, THE implementation SHALL document
the dependency, its rationale, and compatibility risk.

WHEN upgrading Magento or PHP, THE implementation SHALL identify deprecated, removed, or
incompatible APIs and update affected customizations accordingly.

*Rationale*: The `vendor/` directory is Composer-managed and reset on every dependency
update; core modifications are silently lost or create unmergeable divergence.

### IX. Configuration and Deployment Safety
WHEN configuration is introduced, THE implementation SHALL follow Magento's standard
configuration architecture and scope behavior.

WHEN database schema or data changes are required, THE implementation SHALL use
Magento-supported declarative schema, data patches, schema patches, or equivalent
mechanisms appropriate to the Magento version.

THE implementation SHALL ensure deployment and rollback considerations are addressed for
database and configuration changes.

WHEN configuration affects production performance or behavior, THE implementation SHALL
provide safe defaults and SHALL avoid unexpected environment-specific behavior.

*Rationale*: Declarative schema and patches keep environments reproducible and enable
`setup:upgrade` to run safely and repeatably across development, staging, and
production.

### X. Version Control and Feature Branches
WHEN implementing a feature, enhancement, bug fix, or architectural change, THE work
SHALL be performed from an appropriate feature branch based on the current target/base
branch.

THE feature branch SHALL remain synchronized with its intended base branch according to
the project's branching strategy.

WHEN changes are ready for integration, THE implementation SHALL contain focused commits
that clearly represent logical changes.

THE implementation SHALL NOT mix unrelated refactoring, formatting changes, or feature
work in the same logical change unless required.

WHEN resolving merge conflicts, THE implementation SHALL preserve the intended behavior
of both the feature and the latest valid base-branch changes.

BEFORE integration, THE feature branch SHALL pass applicable static analysis,
coding-standard checks, unit tests, integration tests, and relevant functional tests.

*Rationale*: Isolated, synchronized feature branches with focused commits keep history
reviewable and make it possible to bisect regressions.

### XI. Quality Gates
WHEN a change is submitted for review, THE change SHALL satisfy the following minimum
quality gates:

- Magento architecture compliance.
- Applicable PHP-FIG PSR compliance.
- Magento coding-standard compliance.
- Static analysis without unresolved critical errors.
- Appropriate automated test coverage.
- No known regression in existing functionality.
- UX consistency for customer-facing changes.
- No avoidable performance regression.
- Security requirements satisfied.
- No direct modification of Magento core/vendor code.
- Documentation updated when behavior, configuration, architecture, or public
  interfaces change.

IF any mandatory quality gate fails, THEN the change SHALL NOT be considered
production-ready until the failure is resolved or explicitly documented and approved as
an exception.

IF a requirement cannot be satisfied due to a Magento, PHP, infrastructure, or
backward-compatibility constraint, THEN the exception SHALL be documented with its
rationale, impact, and mitigation.

*Rationale*: A single, explicit checklist gives reviewers and automated CI a shared,
unambiguous definition of "done" and "production-ready."

### XII. Principle of Least Change
WHEN implementing a requirement, THE implementation SHALL make the smallest
architectural and behavioral change necessary to satisfy the requirement while
preserving existing Magento behavior.

WHEN an existing Magento capability already satisfies the requirement, THE
implementation SHALL reuse it instead of creating a parallel implementation.

WHEN refactoring is not required for the requested behavior, THE implementation SHALL
avoid unrelated refactoring.

*Rationale*: Minimizing blast radius reduces regression risk and keeps code review
focused on the actual requirement being delivered.

## Reference Standards

- Magento / Adobe Commerce Architecture: https://developer.adobe.com/commerce/docs
- PHP Standards Recommendations (PSR): https://www.php-fig.org/psr/

THE implementation SHALL treat these references as the authoritative sources for
architectural conventions and coding standards cited throughout the Core Principles.

## Acceptance Criteria

THE constitution SHALL treat correctness, maintainability, Magento architectural
compliance, testability, UX consistency, security, and performance as first-class
acceptance criteria for every implementation. No change SHALL be considered complete if
it satisfies functional requirements while violating one or more Core Principles above,
unless an exception has been documented and approved per Principle XI (Quality Gates).

## Governance

This constitution supersedes all other engineering practices, style guides, and ad hoc
conventions for this repository. Where this document and another project document
conflict, this constitution governs.

**Amendment procedure**: Amendments are proposed by editing
`.specify/memory/constitution.md`, describing the change and rationale, and updating the
Sync Impact Report at the top of the file. Amendments take effect once merged to the
project's base branch per Principle X (Version Control and Feature Branches).

**Versioning policy**: This constitution is versioned using semantic versioning
(MAJOR.MINOR.PATCH):
- MAJOR: Backward-incompatible governance or principle removals/redefinitions.
- MINOR: New principle or section added, or materially expanded guidance.
- PATCH: Clarifications, wording, typo fixes, or non-semantic refinements.

**Compliance review**: Every change SHALL be evaluated against the Quality Gates defined
in Principle XI before being considered production-ready. Reviewers SHALL reject changes
that violate a Core Principle unless an explicit, documented exception has been
approved. Dependent templates (plan, spec, tasks, checklist) SHALL be reviewed for
alignment whenever this constitution is amended, per the Sync Impact Report.

**Version**: 1.0.0 | **Ratified**: 2026-09-12 | **Last Amended**: 2026-09-12
