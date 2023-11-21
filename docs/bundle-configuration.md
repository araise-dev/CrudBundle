# Bundle Configuration
You can configure this bundle by creating a file under `config/packages/araise_crud.yaml` that looks like this:
```yaml
# config/packages/araise_crud.yaml
araise_crud:
```

## Configuration Options
Under the `araise_crud` key you can use any of the following options:

### `enable_turbo`
ℹ️ Note: If you don't specify this option, it will take the one from the CoreBundle. For more infos on the effects, consult the [docs of the CoreBundle](https://core.docs.araise.dev/#/bundle-configuration).

| Type    | Default                                                                        | Description                                    |
|---------|--------------------------------------------------------------------------------|------------------------------------------------|
| Boolean | Consult [CoreBundle docs](https://core.docs.araise.dev/#/bundle-configuration) | Is used to decide whether turbo is used or not |
