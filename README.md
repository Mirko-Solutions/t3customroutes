# TYPO3 Extension "Custom Routes"

- Authors: Mirko Team

- Email: support@mirko.in.ua

- Website: [mirko.in.ua](https://mirko.in.ua/)

The "Custom Routes" extension (`EXT:t3customroutes`) provide possibility to declare own api endpoints with own url

## Installation

```sh 
composer require mirko/t3customroutes 
```

## Configuration

import route enhancer by adding following line on bottom of your site `config.yaml`.

```yaml
imports:
  - { resource: "EXT:t3customroutes/Configuration/Routing/config.yaml" }
```

If you do not want to use import you can also manually add new route enhancer of type T3apiResourceEnhancer directly in
your site configuration.

```yaml
routeEnhancers:
  CustomRoutes:
    type: RoutesResourceEnhancer
```

## Creating custom api endpoint

Next step is to register custom routes in `EXT:{extkey}/Configuration/routes.yaml`

#### Creating Routes in YAML

```yaml
# config/routes.yaml
blog_list:
  path: /blog
  # the controller value has the format 'controller_class::method_name'
  controller: App\Controller\BlogController::list
```

#### Matching HTTP Methods

By default, routes match any HTTP verb (`GET`, `POST`, `PUT`, etc.) Use the methods option to restrict the verbs each
route should respond to:

```yaml
# config/routes.yaml
api_post_show:
  path: /api/posts/{id}
  controller: App\Controller\BlogApiController::show
  methods: GET

api_post_edit:
  path: /api/posts/{id}
  controller: App\Controller\BlogApiController::edit
  methods: PUT
```

#### Method Parameters

```php
class BlogController
{
    public function list(int $page)
    {
        return 'basic api route';
    }
}
```

```yaml
# config/routes.yaml
blog_list:
  path: /blog/{page}
  controller: App\Controller\BlogController::list
  defaults:
    page: 1

blog_show:
# ...
```

You may also need to configure dependency injection in your extensions

[TYPO3 main documentation](https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/DependencyInjection/Index.html)

### Contributing

You can contribute by making a **pull request** to the master branch of this repository.
