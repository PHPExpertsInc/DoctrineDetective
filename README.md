# Doctrine Detective Bundle

**Doctrine Detective** is a Symfony2 Bundle that provides a detailed SQL query 
log for both HTML and JSON responses, including the SQL query, its location, 
and duration, organized by Controller -> Service -> Repository.

It is mainly useful for debugging, profiling and refactoring your Doctrine ORM
queries into far more efficient Doctrine DBAL queries.

Unlike other SQL loggers, *Doctrine Detective* has the following features:

1. Queries are organized hierarchically by class and method.
2. Prepared statements have the parameters interpolated, so you can directly query
them against the database.
3. RESTful API support.

## Installation

0. Add `"phpexpertsinc/DoctrineDetective" : "1.0.0"` to your *composer.json*.
1. Run `$ composer install`.
2. Edit `app/appKernel.php`.
3. Add `new PHPExperts\DoctrineDetectiveBundle\DoctrineDetectiveBundle(),` to
the `AppKernel::registerBundles()` array. -or- (**prefered**), add it to just
the `dev` and `test` environments:
```
    if (in_array($this->getEnvironment(), array('dev', 'test'))) {
        $bundles[] = new PHPExperts\DoctrineDetectiveBundle\DoctrineDetectiveBundle();
    }
```

## Output

### HTML Response

At the end of every HTML response, you will find the following:

    <div class="doctrineDetective-SQLLog">
        <table>
            <tr>
                <td>TestController::getActiveUsersAction</td>
                <td>3.886604999847429 ms</td>
                <td>-</td>
            </tr>
            <tr>
                <td>UserService</td>
                <td>3.37965652160646 ms</td>
                <td>-</td>
            </tr>
            <tr>
                <td>UserService::getUsers(), Line 210</td>
                <td>2.622127532959 ms</td>
                <td>SELECT * FROM users WHERE ids IN (1, 2, 3, 4, 5)</td>
            </tr>
            <tr>
                <td>UserRepository</td>
                <td>0.75697898864746 ms</td>
                <td>-</td>
            </tr>
            <tr>
                <td>UserRepository::isActive(), Line 115</td>
                <td>0.75697898864746</td>
                <td>SELECT last_visit FROM login_log WHERE userId IN (1, 2, 3, 4, 5)</td>
            </tr>
        </table>
    </div>


### JSON Response

At the end of your JSON response, you will find the `sqlLog` array:


    "sqlLog": {
        "TestController::getActiveUsersAction": {
            "time": 3.886604999847429,
            "UserService": {
                "time": 3.37965652160646,
                "getUsers": {
                    "time": 2.622127532959,
                    "queries": [
                        {
                            "query": "SELECT * FROM users WHERE ids IN (1, 2, 3, 4, 5)", 
                            "line": 210,
                            "time": 2.622127532959
                        }
                    ]
                },
            },
            "UserRepository": {
                "isActive": {
                    "time": 0.75697898864746,
                    "queries": [
                        {
                            "query": "SELECT last_visit FROM login_log WHERE userId IN (1, 2, 3, 4, 5)", 
                            "line": 115,
                            "time": 0.75697898864746
                        }
                    ]
                },
            }
        }
    }
