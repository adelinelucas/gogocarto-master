v3.3.0
======
* FEATURE: Read and write data to OpenStreetMap with dynamic import
* FEATURE: Visitor stats component on dashboard, connected with matomo instance
* FEATURE: The app can now internationalized, translations are in progress

v3.2.7
======
* BREAKING CHANGE: the docker-composer.yml has been moved to the root of the project. If you have any docker-compose.local.yml, please move it also to the root

v3.2.1
======
* MAJOR: The way of managing SAAS instance cron jobs have changed. In your main database run `db.Project.updateMany({}, {$currentDate: {nextUpdateAt: true}})` and check `docs/installation.md` for new cron jobs configuration

v3.1.4
======
* FEATURE: Ability to configure a custom domain in SASS mode (if the server uses nginx)
* CONFIG: you need to add new entry in your .env.local : DATABASE_NAME=gogocarto_default 

v3.0.0
======
* MAJOR: Upgrade to php7 (mandatory) and symfony 4.4.
* FEATURE: Ability to include news in the automated newsletter
* FEATURE: Improve SAAS homepage. ability to filter and sort projects, and paginate
* FEATURE: Improve SAAS management. Get more info about each map, warn abandonned projects
* BUG: Fix element export
* BUG: Fix hard deleting elements
* FEATURE: Search on multi fields. Can be configured from the Form Builder

v2.5.8
======
* BUG: Import Categories with space at beginning or the end

v2.5.7
======
* BUG: Prevent circular reference between categories and options

v2.5.6
======
* FEATURE: Service Worker so GoGoCarto works better on mobile
* FEATURE: Ability to choose multiple attribute to fill the categories
* BUG: MongoDB Broken associations betwwen Contribution and Elements
* BUG: Moderation custom message get lost on refuse pending element
* FEATURE: Import use customFormatedAddress
* FEATURE: Import handle better bad formats and corrupt data
* BUG: Fix Element Export from admin
* FEATURE: Custom style is now also applied to SAAS pages
* BUG: Prevent merging records where no address and no lat/long is provided
* FEATURE: Improve Search

v2.5.1
======
* BUG: In Element Import Taxonomy Mapping, all mapping was deleted on save

v2.5
======
* FEATURE: Ability to import files and images in the public form
* BUG: private properties in API was not working properly
* BUG: users were able to vote to their own contribution
* IMPROVEMENTS: in element import. Better match, prevent using dot in mapping field...

v2.4
======

* FEATURE: Ability to add excluding words for element search
* FEATURE: Complete reorganisation of user role management. Please update your users & groups roles
* FEATURE: Element import, ability to map ontology and taxonomy
* BUG: Fix list mode

v2.3.1
======

* FEATURE: Display number of messages and errors in admin top header
* FEATURE: Add license url for the data
* BUG: Fix using images in marker popup template

v2.3
====

* BUG: Updating multiple source at one time, count was not reseted
* FEATURE: Sort sub options by alphabetical order
* FEATURE: In the map categories menu, display next to each categorie how many elements are available for this category
* FEATURE: Edit marker popup template
* FEATURE: Ability to destroy my project
* DOCS: Improve readability of admin interface text helpers
* CODE: Architecture for DB migrations and Messages Changelogs
* FEATURE: Webhooks availables !
* BUG: Fix form builder labels and unique name edit
* FEATURE: Form Image Field available (with url, not uploading)
* BUG: Import CSV with empty header column


v2.2
====

* Creation of changelog