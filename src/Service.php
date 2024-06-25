<?php

namespace Nerow\Services;

use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

abstract class Service
{
    /**
     * Name of the model class
     */
    protected string $model;

    /**
     * Exclude fields from create/update
     */
    protected array $except = [];

    /**
     * Only create/update this fields
     */
    protected array $only = [];

    public function __construct()
    {
        $this->model = $this->model ?? $this->getModelName();
    }

    /**
     * Retrieves model name extract from the service name
     */
    protected function getModelName(): string
    {
        return '\\App\\Models\\' . str_replace(['\\', 'Service'], '', substr($this::class, strrpos($this::class, '\\')));
    }

    /**
     * Return the model name
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Define rules for each model fields
     */
    public function rules(Request $request): array
    {
        return [];
    }

    /**
     * Customize error messages for rules
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * Allow field modification before rule validation
     */
    protected function beforeValidation(Request $request): array
    {
        return [];
    }

    /**
     * Make a new DB instance
     */
    public function create(Request|array $attributes): Model
    {
        $request = $this->prepare($attributes, 'create');

        $safe = $this->validate($request->all(), $this->rules($request));

        $model = $this->model::create($safe);

        return $model;
    }

    /**
     * Update an existing DB instance
     */
    public function update(Model|int $model, Request|array $attributes): Model
    {
        $model = $this->modelResolver($model);

        $request = $this->prepare($attributes, 'update');

        $model->fill($request->all());

        $safe = $this->validate(
            $request->only(array_keys($model->getDirty())),
            $this->sometimes($this->rules($request))
        );

        if (empty($safe)) {
            $model->touch();
            
            return $model;
        }

        $model->update($safe);

        $model->refresh();

        return $model;
    }

    /**
     * Delete an instance from the DB
     */
    public function delete(Model|int $model): Model
    {
        return tap($this->modelResolver($model))->delete();
    }

    /**
     * Update an instance from $confitions or make a new
     */
    public function updateOrCreate(array $conditions, Request|array $attributes): Model
    {
        $request = $this->requestResolver($attributes);

        $model = $this->model::where($conditions)->first();

        return empty($conditions) || ! $model
            ? $this->create(array_merge($conditions, $request->all()))
            : $this->update($model, $request);
    }

    /**
     * Duplicate an instance
     */
    public function duplicate(Model|int $model, ...$options): Model
    {
        return $this->create($this->modelResolver($model)->toArray(...$options));
    }

    /**
     * Convert integer into model
     */
    protected function modelResolver(Model|int &$model): Model
    {
        return is_int($model) ? $this->model::findOrFail($model) : $model;
    }

    /**
     * Convert array into Request
     */
    protected function requestResolver(Request|array $request): Request
    {
        return is_array($request) ? new Request($request) : $request;
    }

    /**
     * Prepare the request
     */
    protected function prepare(Request|array $attributes, string $method): Request
    {
        $request = $this->requestResolver($attributes);

        foreach ($this->beforeValidation($request) as $key => $value) {
            if ($request->has($key)) $request->merge([$key => $value]);
        }

        $collection = $request->collect();

        foreach (['except', 'only'] as $key) {
            $values = $this->$key;

            $values = collect($values)
                ->merge(data_get($values, $method, []))
                ->except(['create', 'update'])
                ->toArray();

            if (! empty($values)) $collection = $collection->$key($values);
        }

        return new Request($collection->toArray());
    }

    /**
     * Add 'sometimes' rule to required field when update
     */
    protected function sometimes(array $rules): array
    {
        foreach ($rules as &$value) {
            is_string($value)
                ? $value = str_replace('required', 'sometimes|required')
                : array_unshift($value, 'sometimes');
        }

        return $rules;
    }

    /**
     * Validate request data with rules
     */
    protected function validate(array $data, array $rules): array
    {
        try {
            return \Validator::make($data, $rules, $this->messages())->validate();
        } catch (ValidationException $e) {
            throw $this->validationException($e->errors());
        }
    }

    /**
     * Rebuild ValidationException
     */
    protected function validationException(array $errors): ValidationException
    {
        foreach ($errors as $attribute => $messages) {
            $key = substr($attribute, 0, strpos($attribute, '.'));

            $errors[$key] = array_merge(data_get($errors, $key, []), $messages);
        }

        return ValidationException::withMessages($errors);
    }

    /**
     * Aliasing of attach model method
     */
    protected function attach(string $relationName, Model|int $model, ...$args): Model
    {
        $model = $this->modelResolver($model);

        $relation = $model->$relationName();

        $relation->attach(...$args);

        return $model;
    }

    /**
     * Aliasing of detach model method
     */
    protected function detach(string $relationName, Model|int $model, mixed $ids = null): Model
    {
        $model = $this->modelResolver($model);

        $relation = $model->$relationName();

        $relation->detach($ids);

        return $model;
    }
}
