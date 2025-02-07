<?php

namespace Nerow\Services;

use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class Service
{
    const CREATE = 'create';
    const UPDATE = 'update';

    /**
     * Name of the model class
     */
    protected string $model;

    protected string $uid;

    /*
    |--------------------------------------------------------------------------
    | Magic Methods
    |--------------------------------------------------------------------------
    */

    public function __construct()
    {
        $this->uid = uniqid();
        $this->model = $this->model ?? $this->getModelName();
    }

    public static function __callStatic($name, $arguments)
    {
        return (new static)->{'_' . $name}(...$arguments);
    }

    /*
    |--------------------------------------------------------------------------
    | Overridable Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Define rules for each model fields
     */
    public static function rules(?Request $request = null): array
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

    /*
    |--------------------------------------------------------------------------
    | Public Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Return the model
     */
    public function getModel(): Model
    {
        return app($this->model);
    }

    /**
     * Return all the model fields except hidden
     */
    public function getPublicFields(): array
    {
        $model = $this->getModel();

        $fields = array_merge($model->getFillable(), $model->getDates());

        return array_filter(
            array_diff($fields, $model->getHidden()),
            fn ($value) => !is_null($value)
        );
    }

    /**
     * Return rules adapted to the wanted method.
     */
    public static function getRules(string $method, Request $request = null): array
    {
        return array_map(function($line) use ($method) {
            // If the rule line is in string format, make it to array
            $line = is_string($line) ? explode('|', $line) : $line;

            return self::applyImplicitRules($line, $method);
        }, static::rules($request));
    }

    /**
     * Make a new DB instance
     */
    public function _create(Request|array $attributes): Model
    {return $this->model::findOrFail(1);
        $request = $this->prepare($attributes);

        $safe = $this->validate($request->all(), self::getRules(self::CREATE, $request));
        
        $model = $this->model::create($safe);
        
        return $model;
    }

    /**
     * Update an existing DB instance
     */
    public function _update(Model|int $model, Request|array $attributes): Model
    {
        $model = $this->modelResolver($model);

        $request = $this->prepare($attributes);

        $model->fill($request->all());

        $safe = $this->validate(
            $request->only(array_keys($model->getDirty())),
            $this->sometimes(self::getRules(self::UPDATE, $request))
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
    public function _delete(Model|int $model): Model
    {
        return tap($this->modelResolver($model))->delete();
    }

    /**
     * Update an instance from $confitions or make a new
     */
    public function _updateOrCreate(array $conditions, Request|array $attributes): Model
    {
        $request = $this->requestResolver($attributes);

        $model = $this->model::where($conditions)->first();

        return empty($conditions) || ! $model
            ? $this->_create(array_merge($conditions, $request->all()))
            : $this->_update($model, $request);
    }

    /**
     * Duplicate an instance
     */
    public function _duplicate(Model|int $model, ...$options): Model
    {
        return $this->_create($this->modelResolver($model)->toArray(...$options));
    }

    /**
     * Aliasing of attach model method
     */
    public function _attach(string $relationName, Model|int $model, ...$args): Model
    {
        $model = $this->modelResolver($model);

        $relation = $model->$relationName();

        $relation->attach(...$args);

        return $model;
    }

    /**
     * Aliasing of detach model method
     */
    public function _detach(string $relationName, Model|int $model, mixed $ids = null): Model
    {
        $model = $this->modelResolver($model);

        $relation = $model->$relationName();

        $relation->detach($ids);

        return $model;
    }

    /*
    |--------------------------------------------------------------------------
    | Private Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Retrieves model name extract from the service name
     */
    protected function getModelName(): string
    {
        return '\\App\\Models\\' . str_replace(['\\', 'Service'], '', substr($this::class, strrpos($this::class, '\\')));
    }

    /**
     * Convert integer into model
     */
    protected function modelResolver(Model|int &$model): Model
    {
        if (is_int($model)) {
            return $this->model::findOrFail($model);
        }

        $modelName = $this->getModelName();
        if ($model instanceof $modelName) {
            return $model;
        }

        throw new \Exception("Model must be of type $modelName::class, " . $model::class . '::class given');
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
    protected function prepare(Request|array $attributes): Request
    {
        $request = $this->requestResolver($attributes);

        foreach ($this->beforeValidation($request) as $key => $value) {
            if ($request->has($key)) $request->merge([$key => $value]);
        }

        return $request;
    }

    /**
     * Apply implicit rules for create/update actions and remove the prefix if matching (ex: 'create:' or 'update:').
     */
    protected static function applyImplicitRules(array $line, string $method): array
    {
        return array_map(function($rule) use ($method) {
            foreach ([self::CREATE, self::UPDATE] as $action) {
                if (stripos($rule, $action) === 0) {
                    return $action == $method
                        ? str_replace($action . ':', '', $rule)
                        : null;
                }
            }

            return $rule;
        }, $line);
    }

    /**
     * Add 'sometimes' rule to required field when update
     */
    protected function sometimes(array $rules): array
    {
        foreach ($rules as &$value) {
            is_string($value)
                ? $value = str_replace('required', 'sometimes|required', $value)
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
}
