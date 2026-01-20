# Custom Elements Local plugin

The Custom Elements Local plugin provides API that allows other plugins to make use of HTML Custom elements and embed them
in Moodle.

This plugin was contributed by the Open LMS Product Development team. Open LMS is an education technology company
dedicated to bringing excellent online teaching to institutions across the globe.  We serve colleges and universities,
schools and organizations by supporting the software that educators use to manage and deliver instructional content to
learners in virtual classrooms.

## Installation

Extract the contents of the plugin into _/wwwroot/local_ then visit `admin/upgrade.php` or use the CLI script to upgrade your site.

## Usage

### Embedding custom elements in your plugins

To add a custom element to your plugin, first create a folder called `vendorjs` for your plugin.

```
mkdir my/plugin/vendorjs
```

Then, compile your custom element into that folder so it is called `ce.js`.

```
my/plugin/vendorjs/ce.js
```

Now, on the page you want to display your custom element, add code similar to this:

```php
// Render component HTML.
$sesskey = sesskey();
$output = <<<HTML
<my-component
    sess-key="{$sesskey}"
    www-root="{$CFG->wwwroot}"
    user-id="{$USER->id}"
    other-attrbute="Other value"
></my-component>
HTML;
/**
 * @see ce_loader
 */
require_once($CFG->dirroot . '/local/ce/classes/ce_loader.php');
$wcloader = ce_loader::get_instance();
$src = $CFG->wwwroot . '/pluginfile.php/' . $PAGE->context->id . '/my_plugin/vendorjs/ce/ce.js';
$wcloader->register_component('my_plugin/my-component', $src, 'text/javascript');
echo $OUTPUT->box($output, 'boxwidthwide');
```

To allow your plugin to deliver the custom element file, you'll need to add or modify the _pluginfile_ callback in plugin's library.

```php
// my/plugin/lib.php
function my_plugin_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    $pluginpath = __DIR__.'/';
  
    // ...

    if ($filearea === 'vendorjs') {
        $path = $pluginpath.'vendorjs/'.implode('/', $args);
        send_file($path, basename($path));
        return true;
    } 
  
    // ...
}
```

You should now be able to add custom elements built in any JS framework, i.e. Angular, ReactJS, VueJS, etc.

### Calling Moodle service functions

In order for your custom element to interact with Moodle's service functions, the session is needed, that's why we are passing it
as a parameter in the example above.

The following example shows how to make use of Angular to call string functions from Moodle.

This is a general purpose Angular service that allows consuming any Moodle service function:

```ts
import {Injectable} from '@angular/core';

import {Observable, of} from 'rxjs';

import {HttpClient, HttpHeaders} from '@angular/common/http';

import {catchError, map, tap} from 'rxjs/operators';
import {MoodleRes} from "./moodle.res";
import {ErrorReporterService} from "./error-reporter.service";
import {LoaderStatusService} from "./loader-status.service";

@Injectable({
  providedIn: 'root'
})
export class MoodleService {

  protected moodleAjaxUrl = '/lib/ajax/service.php';  // URL to Moodle ajax api.

  protected httpOptions = {
    headers: new HttpHeaders({ 'Content-Type': 'application/json' })
  };

  public wwwRoot: string;
  public sessKey: string;

  constructor(
    private http: HttpClient,
    private errorReporterService: ErrorReporterService,
    private loaderStatusService: LoaderStatusService
  ) {
  }

  service(methodName: string, args: object): Observable<any> {
    let errorRes : MoodleRes[] = [{
      error: "No session key present",
      data: undefined
    }];
    if (!this.sessKey) {
      return of(errorRes);
    }

    errorRes = [{
      error: "No www root present",
      data: undefined
    }];
    if (!this.wwwRoot) {
      return of(errorRes);
    }

    let body = [{
      index: 0,
      methodname: methodName,
      args: args
    }];

    this.loaderStatusService.setLoading(true);
    return this.http.post<MoodleRes[]>(`${this.wwwRoot}${this.moodleAjaxUrl}?sesskey=${this.sessKey}`, body, this.httpOptions)
      .pipe(
        tap(_ => this.log(`Consuming Moodle service ${methodName}`)),
        catchError(this.handleError<MoodleRes[]>(`Moodle service ${methodName}`, [])),
        tap(_ => this.loaderStatusService.setLoading(false))
      );
  }

  services(methodName: string, args: object[]): Observable<any> {
    let errorRes : MoodleRes[] = [{
      error: "No session key present",
      data: undefined
    }];
    if (!this.sessKey) {
      return of(errorRes);
    }

    errorRes = [{
      error: "No www root present",
      data: undefined
    }];
    if (!this.wwwRoot) {
      return of(errorRes);
    }

    let body = [];

    for (let i = 0; i < args.length; i++) {
      body.push({
        index: i,
        methodname: methodName,
        args: args[i]
      });
    }

    this.loaderStatusService.setLoading(true);
    return this.http.post<MoodleRes[]>(`${this.wwwRoot}${this.moodleAjaxUrl}?sesskey=${this.sessKey}`, body, this.httpOptions)
      .pipe(
        tap(_ => this.log(`Consuming Moodle service ${methodName}`)),
        catchError(this.handleError<MoodleRes[]>(`Moodle service ${methodName}`, errorRes)),
        tap(_ => this.loaderStatusService.setLoading(false))
      );
  }

  private log(message: string) {}

  /**
   * Handle Http operation that failed.
   * Let the app continue.
   * @param operation - name of the operation that failed
   * @param result - optional value to return as the observable result
   */
  private handleError<T>(operation = 'operation', result?: T) {
    return (error: any): Observable<T> => {

      // TODO: send the error to remote logging infrastructure
      this.errorReporterService.relayError(error);

      // TODO: better job of transforming error for user consumption
      this.log(`${operation} failed: ${error.message}`);

      // Let the app keep running by returning an empty result.
      return of(result as T);
    };
  }

  public extractData(response: any) : any {
    if (!response.length) {
      // Single response with error arrived.
      let singleMoodleRes: MoodleRes = response;
      if (singleMoodleRes.error) {
        this.errorReporterService.relayError(singleMoodleRes);
        return null;
      }

      return singleMoodleRes.data;
    }

    let multiMoodleRes: MoodleRes[] = response;

    if (multiMoodleRes[0].error) {
      this.errorReporterService.relayError(multiMoodleRes[0]);
      return null;
    }

    return multiMoodleRes[0].data;
  }
}
```

Now the Angular service that actually calls Moodle's service functions:

```ts
import {Injectable} from '@angular/core';

import {Observable, of} from 'rxjs';

import {MoodleService} from "./moodle.service";
import {map, tap} from "rxjs/operators";

@Injectable({
  providedIn: 'root'
})

export class StringService {
  private cachedStrings: string[] = [];

  constructor(
    private moodleService: MoodleService
  ) {
  }

  getStrings(stringIds: string[]): Observable<string[]> {
    const methodName = 'core_get_strings';

    let strArgs = [], cachedIds: string[] = [];
    for (let i = 0; i < stringIds.length; i++) {
      let stringId = stringIds[i];
      let component = 'my_plugin';
      if (stringId.indexOf(':') != -1) {
        const splitted = stringIds[i].split(':');
        stringId = splitted[0];
        component = splitted[1];
      }
      if (this.cachedStrings[stringId]) {
        cachedIds.push(stringId);
        continue;
      }
      strArgs.push({
        stringid: stringId,
        component: component
      });
    }

    if (strArgs.length === 0) {
      return of(this.processStrings(cachedIds, []));
    }

    const requestBody = {
      strings: strArgs
    };

    return this.moodleService.service(methodName, requestBody).pipe(
      map(this.moodleService.extractData),
      map(stringData => {
        return this.processStrings(cachedIds, stringData);
      })
    );
  }

  processStrings(cachedIds: string[], stringData: any[]) : string[] {
    let res: string[] = [];

    // Look for cached strings.
    for(let i = 0; i < cachedIds.length; i++) {
      res[cachedIds[i]] = this.cachedStrings[cachedIds[i]];
    }

    // Get strings from request and cache them too.
    for(let i = 0; i < stringData.length; i++) {
      res[stringData[i].stringid] = stringData[i].string;
      this.cachedStrings[stringData[i].stringid] = stringData[i].string;
    }

    return res;
  }
}
```

Now, you can use this in an Angular component of your liking:

```ts
import {Component, Input, OnInit, ViewEncapsulation} from '@angular/core';
import {Router} from "@angular/router";
import {MoodleService} from "../moodle.service";
import {StringService} from "../string.service";
import {Observable} from "rxjs";

@Component({
  selector: 'my-component',
  template: `
    <div class="container-fluid">
      <div class="row">
        <div class="col">
            <h3>{{strings['coolstring1']}}</h3>
        </div>
        <div class="col">
            <h3>{{strings['coolstring2']}}</h3>
        </div>
      </div>
    </div>
  `,
  styles: [],
  encapsulation: ViewEncapsulation.None // This allows the custom element to make use of Moodle's styles.
})
export class MyComponent implements OnInit {
  @Input() sessKey: string;
  @Input() wwwRoot: string;
  @Input() userId: number;
  @Input() otherAttrbute: string;

  strings: string[];

  constructor(
    private router: Router,
    private moodleService: MoodleService,
    private stringService: StringService
  ) {}


  ngOnInit() {
    this.moodleService.sessKey = this.sessKey;
    this.moodleService.wwwRoot = this.wwwRoot;
    this.getStrings().subscribe(strings => {
      this.router.initialNavigation();
    });
  }

  getStrings(): Observable<string[]> {
    this.strings = [];
    let observable = this.stringService.getStrings([
      'coolstring1', 
      'coolstring2',
      '...'
    ]);
    observable.subscribe( strings => {
      this.strings = strings;
    });
    return observable;
  }
}
```

To export this component as a custom element, you need to do something like this:

```ts
import {BrowserModule} from '@angular/platform-browser';
import {CUSTOM_ELEMENTS_SCHEMA, Injector, NgModule} from '@angular/core';

import {MyComponent} from './my-component/my.component';

import { HttpClientModule } from '@angular/common/http';

@NgModule({
  declarations: [
    MyComponent,
  ],
  imports: [
    BrowserModule,
    HttpClientModule,
  ],
  entryComponents: [MyComponent],
  providers: [],
  schemas: [CUSTOM_ELEMENTS_SCHEMA],
})
export class AppModule {

  constructor(private injector: Injector) {
  }

  ngDoBootstrap() {
    const myComponent = createCustomElement(MyComponent, {injector: this.injector});
    customElements.define('my-component', myComponent);
  }
}

Enjoy!

```

## Flags

### The `local_ce_enable_usage`flag.

## License

Copyright (c) 2021 Open LMS (https://www.openlms.net)

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see [http://www.gnu.org/licenses/](http://www.gnu.org/licenses/).
