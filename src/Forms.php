<?php

namespace Mate\Forms;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Spatie\Html\Elements\Button;
use Spatie\Html\Elements\Div;
use Spatie\Html\Html;

class Forms extends Html
{
    /**
     * Creates a new Form Tag for a model.
     *
     *
     *
     * @param  string  $baseRoute
     *
     *      Is the base route name for the Controller.
     *      For example, the model User, will have a base route named user.
     *      The create route for the model will be called user.create, the action for the store method will be user.store.
     *      The edit route for the model will be called user.edit, the action for the update method will be user.update.
     *      This method will append .store or .update to the base route name depending on if the model is set or not.
     *      This method assumes that Store route has the model class name as a named parameter used
     *       for model binding in the controller. For example "/user/{user}"
     * @param  Model|null  $model
     *                             The model for edit, null if is a create form.
     * @param  bool  $acceptsFiles
     *                              Add 'enctype= multipart/form-data' attribute to accept files in the form.
     * @return Htmlable|HtmlString
     */
    public function create(string $baseRoute, ?Model $model = null, $acceptsFiles = false)
    {
        //check that the eloquent model is new
        if (! empty($model) && $model->exists) {
            $method = 'PUT';
            $this->model($model);
            $url = route($baseRoute.'.update', [strtolower(class_basename(get_class($model))) => $model->id]);
        } else {
            $method = 'POST';
            $this->model(null);
            $url = route($baseRoute.'.store');
        }

        $form = $this->form($method, $url);

        if ($acceptsFiles) {
            $form = $form->acceptsFiles();
        }

        return $form->open();
    }

    /**
     * Close a form tag.
     */
    public function end(): Htmlable
    {
        return $this->form()->close();
    }

    /**
     * @param  string  $label
     *                         The human-readable label for the field.
     * @param  string  $name
     *                        The field name in the model
     * @param  string  $type
     *                        The HTML type of the field
     * @param  string|null  $value
     *                              The Value for the Field
     * @param  array  $options
     *                          Depending on the type, the options possible.
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function field(string $label, string $name, string $type = 'text', ?string $value = null, array $options = []): Div
    {
        $label = $this->label($label, $name)
            ->addClass('strong mb-1');
        $cssClass = 'form-control';
        $readOnly = isset($options['readonly']) ? $options['readonly'] : false;
        switch ($type) {
            case 'text':
            case 'input':
            case 'email':
            case 'hidden':
            case 'tel':
            case 'date':
            case 'datetime-local':
            case 'password':
                $control = $this->input($type, $name, $value);
                break;
            case 'textarea':
                $control = $this->textarea($name, $value);
                break;
            case 'number':

                $control = $this->input($type, $name, $value)->isReadonly($readOnly)
                    ->attribute('step', 'any');
                break;
            case 'select':
                $control = $this->select($name, $options, $value)->isReadonly($readOnly);
                $cssClass = 'form-select';
                break;
            case 'multiselect':
                $control = $this->multiselect($name, $options, $value);
                break;
            case 'checkbox':

                $control = $this->checkbox($name, ! (empty($value)), $value);
                break;
            case 'checkboxes':
                $control = $this->div()->class('form-checks');
                foreach ($options as $key => $option) {
                    $values = [];
                    if (! empty($this->model) && $this->model->exists) {
                        $values = $this->model->roles->pluck('id')->toArray();
                    }
                    $checked = (! empty($values) && in_array($key, $values));
                    $wrapper = $this->div()->class('form-check');
                    $wrapper = $wrapper->addChild($this->checkbox($name.'[]', $checked, $key));
                    $wrapper = $wrapper->addChild($this->label(ucfirst(strtolower($option))));
                    $control = $control->addChild($wrapper);
                }
                break;
            case 'radio':
                $checked = ! empty($options['checked']);
                $control = $this->radio($name, $checked, $value);
                break;
        }

        $control = $control->addClass($cssClass);

        $group = $this->div()->class('form-group mb-3')
            ->addChild($label);

        $e = $this->error($name);

        if (! empty($e)) {
            $control = $control->addClass('is-invalid');
        }

        return $group->addChild($control)
            ->addChild($e);
    }

    /**
     * Returns a DropDown item for the given field name.
     *
     * @return Div
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function dropdown(string $label, string $name, array $options = [], ?string $value = null)
    {
        return $this->field($label, $name, 'select', $value, $options);
    }

    public function checkboxes(string $label, string $name, array $options = [], ?string $value = null)
    {
        return $this->field($label, $name, 'checkboxes', $value, $options);
    }

    public function datepicker(string $label, string $name, $format = true)
    {
        return $this->field($label, $name, 'date');
    }

    /**
     * For the given field name, checks if there is any valdiation errors.
     *
     * @param  string  $name
     *                        The name of the field to check for errors.
     * @return Div|null
     *                  Returns a Div element with the error messages if there are any, otherwise null.
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function error(string $name): ?Div
    {
        $e = null;
        $errorMessages = '';
        $errors = session()->get('errors');

        if (! empty($errors) && $errors->has($name)) {
            foreach ($errors->get($name) as $message) {
                $errorMessages .= $message.'<br>';
            }
        }

        if (! empty($errorMessages)) {
            $e = $this->div($errorMessages)
                ->addClass('invalid-feedback');
        }

        return $e;
    }

    /**
     * The validation errors for the form.
     *
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function errors(): ?Div
    {
        $e = null;
        $errorMessages = '';
        $errors = session()->get('errors');
        if (! empty($errors)) {
            foreach ($errors->all() as $message) {
                $errorMessages .= $message.'<br>';
            }
        }

        $error = session()->get('error');
        if (! empty($error)) {
            $errorMessages .= $error.'<br>';
        }

        if (! empty($errorMessages)) {
            $e = $this->div($errorMessages)
                ->id('error-messages')
                ->addClass('alert alert-danger');
        }

        return $e;
    }

    /**
     * The success messages.
     *
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function success(): ?Div
    {
        $e = null;

        $message = session()->get('success');

        if (! empty($message)) {
            $e = $this->div($message)
                ->addClass('alert alert-success');
        }

        return $e;
    }

    /**
     * The errors and the messages wrapped into a div.
     */
    public function messages(): ?Div
    {
        $messages = $this->div()->class('messages');

        if ($success = $this->messages() && ! empty($success)) {
            $messages = $this->div()
                ->addChild($success);
        }

        if ($errors = $this->errors() && ! empty($errors)) {
            $messages = $this->div($errors);
        }

        return $messages;
    }

    /**
     * @param  string|null  $type
     * @param  string|null  $text
     * @return \Spatie\Html\Elements\Button
     */
    public function button($contents = null, $type = null, $name = null)
    {
        return Button::create()
            ->attributeIf($type, 'type', $type)
            ->attributeIf($name, 'name', $this->fieldName($name))
            ->html($contents)
            ->addClass('btn');
    }

    public function submit($text = null)
    {
        return parent::submit($text)->addClass('btn-primary');
    }
}
