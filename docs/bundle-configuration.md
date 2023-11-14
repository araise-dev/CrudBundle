# Bundle Configuration
You can configure this bundle by creating a file under `config/packages/araise_crud.yaml` that looks like this:
```yaml
# config/packages/araise_crud.yaml
araise_crud:
```

## Configuration Options
Under the `araise_crud` key you can use any of the following options:

### `enable_turbo`
ℹ️ Note: This option comes from the CoreBundle. For more infos on the effects, consult the [docs of the CoreBundle](https://core.docs.araise.dev/#/bundle-configuration).

| Type    | Default | Description                                    |
|---------|---------|------------------------------------------------|
| Boolean | `false` | Is used to decide whether turbo is used or not |
