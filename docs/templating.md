# Templating

## Layout Based Rendering

```yaml
araise_crud:
  templateDirectory: '@araiseCrud/Templates'
```

The template path can also be changed individually for each definition class. 

```php
class PostDefinition extends AbstractDefinition
{
    // ...
    public function getTemplateDirectory(): string
    {
        return 'myTemplates';
    }
    // ...
}
```

## Layout Files

The layout file defines how the crud will be rendered. The default layout file is `@araiseCrud/layout/adminlte_layout.html.twig` 
and can be changed in the config `config/packages/araise_crud.yaml`

```yaml
araise_crud:
  layout: 'crud/layout/my_layout.html.twig'
```

Like in Symfony Forms, the layout file can be extended or overwritten. New blocks can be added or overwritten. 

```twig
{% extends '@araiseCrud/layout/adminlte_layout.html.twig' %}

{% block crud_show %}
    my fancy show block
    {{ parent() }}
{% endblock %}

```

## Twig-Functions and Blocks


### Main Twig-Functions
| Twig-Function  | Default Blockname | with Blockprefix        | -----                     |
|--------------- |----------         | ----                    | -----                     |
|`crud_show`     |`show`             | N/A                     | `render_mode = 'edit'`    |
|`crud_create`   |`create`           | N/A                     | `render_mode = 'create'`  |
|`crud_edit`     |`edit`             | N/A                     | `render_mode = 'create'`  |
|`crud_table`    |`table`            | `<block_prefix>_table`  | `render_mode = 'create'`  |


### `crud_content_row` - Twig-Function 

The row block name will be dynamically created. `<block_prefix>` and `<render_mode>` will be used to create the block name.

### `<block_prefix>`

The `block_prefix` can be set with the content options.

```php
class PostDefinition extends AbstractDefinition
{
    // ...

    public function configureView(DefinitionBuilder $builder, $data)
    {
        $builder
            ->getBlock('post')
            ->addContent(
                'content',
                null,
                [
                    'label' => 'post.content',
                    'block_prefix' => 'postcontent'
                ]
            )
        ;
    }

    // ...
}
```

By default the `block_prefix` is the snake case of the class name. 

| Class                                        | Default Block - Prefix |
|----------------------------------------------|------------------------|
| `\araise\CrudBundle\Content\Content`         | `content`              |
| `\araise\CrudBundle\Content\RelationContent` | `relation_content`     |
| `\araise\CrudBundle\Content\TwigContent`     | `twig_content`         |
| `\araise\CrudBundle\Content\Content`         | `content`              | 



### Table Rendering

| Twig-Function             | Default Blockname | with Blockprefix | ---   |
|---------------            | ----------        | ----                   | -----                     |
|`crud_table`               | `table`           | `<block_prefix>_table`<br> eg. `post_table`        |
|`crud_table_header_cell`   | `table_header`    | `<block_prefix>_header`<br> eg. `posttitle_header` |
|`crud_table_content_cell`  | `table_cell`      | `<block_prefix>_cell`<br> eg. `posttitle_cell`     |

