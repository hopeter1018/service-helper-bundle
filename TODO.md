# TODO

## ServiceRegistry

-   [ ] Command:
    -   [x] `Registry` Maker / Generator
        -   [ ] options to `extends BaseRegistry`
        -   [ ] auto detect config loader (xml / yml)
            -   [x] search sequence [yaml yml xml]
        -   [ ] service name prefix customization
        -   [ ] register `Registry` in `services.yml`
    -   [ ] `Service` Maker / Generator
        -   may not needed
        -   Coz the service php should implement the generated interface and CompilerPass will do the remaining
-   **Chain** mode
    -   [ ] Just like middleware

## Misc

-   [ ] Remove dependency on Stringy
