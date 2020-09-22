# service-helper-bundle

## Introduction

This bundle aims to generate service registry structure

## Installation

### Require the package

`composer require hopeter1018/service-helper-bundle`

### Add to kernel

#### Symfony 4+ or Symfony Flex

Add `/config/bundles.php`

```php
return [
  ...,
  HoPeter1018\ServiceHelperBundle\HoPeter1018ServiceHelperBundle::class => ['all' => true],
];
```

#### Symfony 2+

Add `/app/AppKernel.php`

```php
$bundles = [
  ...,
  new HoPeter1018\ServiceHelperBundle\HoPeter1018ServiceHelperBundle(),
];
```

### Config

-   No config required in this moment

## Dependencies

-   Global command `php-cs-fixer` required during comand execution

## Usage

### Command

#### `hopeter1018:service-helper:generate-registry`

-   Generate:
    -   registry class php
    -   service interface php
    -   CompilerPass php
    -   service xml
-   Register:
    -   in \*Bundle.php
    -   in \*Extension.php
