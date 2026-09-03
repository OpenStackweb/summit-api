<?php
/**
 * Copyright 2026 OpenStack Foundation
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

return [
    // size of the chunk of db processing for speakers email
    'speakers_process_db_chunk_size' => env('EMAILS_SPEAKERS_PROCESS_DB_CHUNK', 500),
    // size of the chunk of job processing for speakers email
    'speakers_process_job_chunk_size' => env('EMAILS_SPEAKERS_PROCESS_JOB_CHUNK', 200)
];
