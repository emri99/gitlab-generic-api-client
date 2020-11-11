# Gitlab-api-generic-client

[![Build Status](https://img.shields.io/travis/emri99/gitlab-generic-api-client/master.svg?style=flat-square)](https://travis-ci.org/emri99/gitlab-generic-api-client)
[![PHP: 5.4](https://img.shields.io/badge/PHP-5.4-blue.svg?style=flat-square)](http://php.net)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg?style=flat-square)](https://opensource.org/licenses/MIT)

This library has been built keeping in mind that Gitlab move so fast, that 
it becomes hard to apply changes and migration guides on complex code base.

## How it works

This code is inpired by npm package [gitlab-api-client](https://www.npmjs.com/package/gitlab-api-client).  
Next lines descriptions too.

Main principle: All paths are build generically.  
You aren't stick to any specific API version as you will have access to 
all the gitlab API endpoints, even for those that haven't been defined yet.

## Installation

```
composer require emri99/gitlab-generic-api-client
```

## Usage

### Authentication

* Authenticate using HTTP token
````php
$client->authenticate('SECRET-HTTP-TOKEN', GitlabApiClient::AUTH_HTTP_TOKEN);
````

* Authenticate using OAUTH token
````php
$client->authenticate('SECRET-OAUTH-TOKEN', GitlabApiClient::AUTH_OAUTH_TOKEN);
````

### Requesting

* GET request

````php
$client = new GitlabApiClient('https://my.gitlab.com/api/v4');
$branches = $client->projects(1)
    ->repository()
    ->branches()
    ->get()
    
// will send GET request on 
// https://my.gitlab.com/api/v4/projects/1/repository/branches.

foreach($branches as $branch) {
    echo $branch->name, "\n";
}
````

* POST request

````php
# create a variable secret
$variableDatas = $this->getClient()
    ->projects(2)
    ->variables()
    ->post([
        'key' => 'SECRET',
        'value' => 'password'
    ]);
````

* PUT request

````php
# protect a branch
$branchUpdated = $this->getClient()
    ->projects(2)
    ->repository()
    ->branches('master')
    ->protect()->put([
        'developers_can_push' => false,
        'developers_can_merge' => false
    ]);
$done = $branchUpdated->protected;
````

* DELETE request

````php
# delete a branch
$branchUpdated = $this->getClient()
    ->projects(2)
    ->repository()
    ->branches('obsolet-feature')
    ->delete();
````

### Special case

If an url segment is the same than a public method of `GitlabApiClient`, this 
remains possible to build to path correctly.

For example, to build path `user/1/delete`, use:

```
$client->user(1, 'delete');
```

### IDE Completion depending on gitlab api version (optional)

Empty classes can be used to simulate code completion on retrieved object
by installing `emri99/gitlab-generic-api-client-models`.
This [optional package](https://github.com/emri99/gitlab-generic-api-client-models) is tagged by gitlab API version.



*Currently there isn't many versions handled, only the one I'm using. ie: 9.1.4*

> When using this package, retrieved objects **WONT BE** instance of models class.  
> Retrieved objects remains `stdclass`. This is **ONLY** used for IDE completion.

```
composer require emri99/gitlab-generic-api-client-models:YOUR_GITLAB_VERSION --dev
```

YOU **MUST** add phpdoc to use completion like below:

* **GET**
````php
$client = new GitlabApiClient('https://my.gitlab.com/api/v4');

/** 
 * $branches aint really a Branch instance 
 * @var Branch[] $branches 
 */
$branches = $client->projects(1)
    ->repository()
    ->branches()
    ->get(array(
        // parameters
    ))

// $branches is an array of stdclass having 
// the same properties than a Branch class
foreach($branches as $branch) {
    echo $branch->name;
}
````

# Contributing

Thanks for contributing !

Please follow this rules:
* you MUST apply supplied CS-fixer by running `composer run-script cs`
* you MUST write/update the tests
* you SHOULD write documentation
* you MUST write minimum details in pull request description

Squashing many commits to avoid noise on git logs make sense too ;)
