<?php

namespace App\Repository;

use Exception;
use ReflectionClass;
use ReflectionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Schema;

abstract class Repository
{
    /**
     * 相對應 Model class 名稱
     * 
     * @var string|null
     */
    protected ?string $relatedModelClassName = null;

    /**
     * 相對應資料表名稱
     * 
     * @var string|null
     */
    protected ?string $relatedTableName = null;

    /**
     * 建構子
     * 
     * @return void
     * 
     * @throws \Exception
     * @throws \ReflectionException
     */
    public function __construct()
    {
        try {
            $classReflector = new ReflectionClass($this);
        } catch (ReflectionException $e) {
            throw $e;
        }

        // 取得 Repository 前綴名稱與其相對應 Model class 名稱綁定
        $repositoryPrefixbindings = config('repositories.prefix.bindings');

        // 取得子類別之前綴名稱
        $modelClassName = strstr($classReflector->getShortName(), 'Repository', true);

        // 檢查相對應 Model class 是否存在
        if (!class_exists("App\\Models\\$modelClassName") && (!isset($repositoryPrefixbindings[$modelClassName]) || !class_exists($repositoryPrefixbindings[$modelClassName]))) {
            throw new Exception("Class [App\\Models\\$modelClassName] does not exist.");
        }

        // 定義相對應 Model class 名稱
        $relatedModelClassName = $this->relatedModelClassName = class_exists("App\\Models\\$modelClassName")
            ? "App\\Models\\$modelClassName"
            : $repositoryPrefixbindings[$modelClassName];

        // 定義相對應資料表名稱
        $this->relatedTableName = app($this->relatedModelClassName)->getTable();

        try {
            $relatedModelClassReflector = new ReflectionClass($relatedModelClassName);
        } catch (ReflectionException $e) {
            throw $e;
        }

        // 檢查相對應 Model class 之資料型別是否正確
        if (($parentModelClassReflector = $relatedModelClassReflector->getParentClass()) === false || !in_array($parentModelClassReflector->getName(), [User::class, Model::class])) {
            throw new Exception("Type of class [$relatedModelClassName] is incorrect.");
        }
    }

    /**
     * 取得相對應資料表中之所有欄位
     * 
     * @param bool $excludeIdAndTimestamps
     * 
     * @return array<int, string>
     */
    public function getRelatedTableColumns(bool $excludeIdAndTimestamps = false)
    {
        $relatedTableColumns = Schema::getColumnListing($this->relatedTableName);
        $excludeIdAndTimestamps && $relatedTableColumns = array_values(array_filter($relatedTableColumns, fn ($column) => !in_array($column, ['id', 'created_at', 'updated_at', 'deleted_at'])));
        return $relatedTableColumns;
    }

    /**
     * 新增一筆資料
     * 
     * @param array<string, mixed> $datas
     * 
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function insert(array $datas)
    {
        $relatedModelClassName = $this->relatedModelClassName;
        return $relatedModelClassName::create($datas);
    }

    /**
     * 透過多種指定條件更新多筆資料
     * 
     * @param array<string, mixed> $datas
     * @param array<int, array> $conditions
     * 
     * @return int
     */
    public function updateRowsByConditions(array $datas, array $conditions = [])
    {
        return $this->filter($conditions)->update($datas);
    }

    /**
     * 透過多種指定條件刪除多筆資料
     * 
     * @param array<int, array> $conditions
     * 
     * @return int
     */
    public function deleteRowsByConditions(array $conditions = [])
    {
        return $this->filter($conditions)->delete();
    }

    /**
     * 透過多種指定條件取得單筆資料
     * 
     * @param array<int, array> $conditions
     * @param array<int, string> $relations
     * 
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function getFirstRowByConditions(array $conditions = [], array $relations = [])
    {
        return $this->filter($conditions, $relations)->first();
    }

    /**
     * 透過多種指定條件取得多筆資料
     * 
     * @param array<int, array> $conditions
     * @param array<int, string> $relations
     * @param array<int, string> $sortBy
     * 
     * @return \Illuminate\Database\Eloquent\Collection<int, \Illuminate\Database\Eloquent\Model>
     */
    public function getRowsByConditions(array $conditions = [], array $relations = [], array $sortBy = ['id', 'desc'])
    {
        return $this->filter($conditions, $relations)
            ->orderBy(...$sortBy)
            ->get();
    }

    /**
     * 取得所有資料
     * 
     * @param array<int, string> $relations
     * @param array<int, string> $sortBy
     * 
     * @return \Illuminate\Database\Eloquent\Collection<int, \Illuminate\Database\Eloquent\Model>
     */
    public function get(array $relations = [], array $sortBy = ['id', 'desc'])
    {
        $relatedModelClassName = $this->relatedModelClassName;
        return $relatedModelClassName::with($relations)->orderBy(...$sortBy)->get();
    }

    /**
     * 透過多種指定條件取得資料筆數
     * 
     * @param array<int, array> $conditions
     * 
     * @return int
     */
    public function getRowCountByConditions(array $conditions)
    {
        return $this->filter($conditions)->count();
    }

    /**
     * 透過多種指定條件建立 Eloquent Builder
     * 
     * @param array<int, array> $conditions
     * @param array<int, string> $relations
     * 
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function filter(array $conditions, array $relations = [])
    {
        // 定義可用方法
        $availableMethods = [
            'where' => 'where',
            'or_where' => 'orWhere',
            'where_in' => 'whereIn',
            'where_not_in' => 'whereNotIn',
            'where_null' => 'whereNull',
            'where_not_null' => 'whereNotNull',
            'where_between' => 'whereBetween',
            'where_not_between' => 'whereNotBetween'
        ];

        $relatedModelClassName = $this->relatedModelClassName;
        $query = $relatedModelClassName::query()->with($relations);

        foreach ($conditions as $condition) {
            if (!is_array($condition) || count($condition) < 2) {
                continue;
            }

            $key = array_shift($condition);

            if (!in_array($key, array_keys($availableMethods))) {
                continue;
            }

            $method = $availableMethods[$key];
            $query = $query->$method(...$condition);
        }

        return $query;
    }
}