<?php namespace App\Providers;
/**
 * Copyright 2021 OpenStack Foundation
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 **/
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

/**
 * Class RouteServiceProvider
 * @package App\Providers
 */
class RouteServiceProvider extends ServiceProvider {
  /**
   * This namespace is applied to your controller routes.
   *
   * In addition, it is set as the URL generator's root namespace.
   *
   * @var string
   */
  protected $namespace = "App\Http\Controllers";

  /**
   * The path to the "home" route for your application.
   *
   * @var string
   */
  public const HOME = "/";

  /**
   * Define your route model bindings, pattern filters, etc.
   *
   * @return void
   */
  public function boot() {
    //

    parent::boot();
  }

  /**
   * Define the routes for the application.
   *
   * @return void
   */
  public function map() {
    $this->mapApiRoutes();

    $this->mapPublicApiRoutes();

    $this->mapWebRoutes();
  }

  /**
   * Define the "web" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapWebRoutes() {
    Route::middleware("web")
      ->namespace($this->namespace)
      ->group(base_path("routes/web.php"));
  }

  /**
   * Define the "api" routes for the application.
   *
   * These routes are typically stateless.
   *
   * @return void
   */
  protected function mapApiRoutes() {
    Route::prefix("api/v1")
      ->middleware("api")
      ->namespace($this->namespace)
      ->group(base_path("routes/api_v1.php"));

    Route::prefix("api/v2")
      ->middleware("api")
      ->namespace($this->namespace)
      ->group(base_path("routes/api_v2.php"));
  }

  /**
   * Define the "api" routes for the application.
   *
   * These routes are typically stateless.
   *
   * @return void
   */
  protected function mapPublicApiRoutes() {
    Route::prefix("api/public/v1")
      ->middleware("public_api")
      ->namespace($this->namespace)
      ->group(base_path("routes/public_api.php"));

    Route::prefix(".well-known")
      ->middleware("well_known")
      ->namespace($this->namespace)
      ->group(base_path("routes/well_known.php"));
  }
}
