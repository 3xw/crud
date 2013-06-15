[![Build Status](https://travis-ci.org/jippi/cakephp-crud.png?branch=develop)](https://travis-ci.org/jippi/cakephp-crud)
[![Coverage Status](https://coveralls.io/repos/jippi/cakephp-crud/badge.png?branch=develop)](https://coveralls.io/r/jippi/cakephp-crud?branch=develop)

# Introduction

Crud was build to be [scaffolding](http://book.cakephp.org/2.0/en/controllers/scaffolding.html) on steroids, and allow developers to have enough flexibility to use it for both rapid prototyping and production applications, even on the same code base - saving you even more time.

Usually the basic code for controller CRUD actions is very simple, and always look the same - this plugin will add the actions to your controller so you don't have to re-implement them over and over

Crud does not have the same limitations as Cake's own scaffolding, which is 'their way or the highway'. Crud allows you to hook into all stages of a request, only building the controller code needed specifically for your business logic, outsourcing the all the heavy boilerplating to Crud.

Less boilerplate code means less code to maintain, and less code to spend time unit testing.

Crud allows you to both use your own views, from bake or hand-crafted, as well as only adding the code needed to fulfill your application logic, using [events](docs/05-events.md). It is by default compatible with Cake's baked views.

Crud is relying heavily on Cake events, and it's possible to override, extend or disable almost all of Cruds functionality, either globally or for just one specific action.

Crud also provides build in features for JSON and XML [API](docs/08-api.md) for any action you have enabled through Crud - that means no more double work maintaining both a HTML frontend and a JSON and/or XML interface for your applications - saving you tons of time and having a leaner code base.

Crud aims to not get in your way, and if it happens to get in your way, you can change the behavior you don't like very easily.

Crud is [very fast to install](docs/02-installation.md), 2 minutes top.

Crud is very flexible, and have tons of [configuration options](docs/03-configuration.md)

Crud is very well [documented](docs/) and has a [high test coverage](https://coveralls.io/r/jippi/cakephp-crud?branch=develop)

# Documentation

Please check the [docs](docs) folder for documentation and how-to guides

* [Introduction](docs/01-introduction.md)
* [Installation](docs/02-installation.md)
* [Configuration](docs/03-configuration.md)
* [Conventions](docs/04-conventions.md)
* [Events](docs/05-events.md)
* [Related Data](docs/06-related-data.md)
* [Translations](docs/07-translations.md)
* [API](docs/08-api.md)
* [API Field Filter](docs/09-api-field-filter.md)
* [Examples](docs/10-examples.md)
* [Custom actions](docs/20-custom-actions.md)
* [Custom listeners](docs/30-custom-listeners.md)
* [TODO](docs/99-todo.md)
