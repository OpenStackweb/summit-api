<?php
/**
 * Copyright 2020 OpenStack Foundation
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
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LaravelDoctrine\ORM\Facades\Registry;
use models\utils\SilverstripeBaseModel;
// models
use models\summit\SummitMediaFileType;
use Doctrine\Common\Persistence\ObjectRepository;
use LaravelDoctrine\ORM\Facades\EntityManager;
/**
 * Class SummitMediaFileTypeSeeder
 */
final class SummitMediaFileTypeSeeder extends Seeder
{
    public function run()
    {
        $em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        $repository = EntityManager::getRepository(SummitMediaFileType::class);

        DB::setDefaultConnection("model");

        if(!$repository->findOneBy(['name' => "Online presence file upload"])) {
            $type = new SummitMediaFileType();
            $type->setName("Online presence file upload");
            $type->setDescription("Online presence file upload: JPEG or PNG");
            $type->setAllowedExtensions("JPG|JPEG|PNG");
            $type->markAsSystemDefined();
            $em->persist($type);
        }

        if(!$repository->findOneBy(['name' => "Print file upload: PDF, EPS or AI"])) {
            $type = new SummitMediaFileType();
            $type->setName("Print file upload: PDF, EPS or AI");
            $type->setDescription("Print file upload: PDF, EPS or AI");
            $type->setAllowedExtensions("EPS|PDF|AI");
            $type->markAsSystemDefined();
            $em->persist($type);
        }

        if(!$repository->findOneBy(['name' => "Backdrop file upload: PDF only"])) {
            $type = new SummitMediaFileType();
            $type->setName("Backdrop file upload: PDF only");
            $type->setDescription("Backdrop file upload: PDF only");
            $type->setAllowedExtensions("PDF");
            $type->markAsSystemDefined();
            $em->persist($type);
        }

        if(!$repository->findOneBy(['name' => "Presentation slides: (Keynote, powerpoint or JPEG)"])) {
            $type = new SummitMediaFileType();
            $type->setName("Presentation slides: (Keynote, powerpoint or JPEG)");
            $type->setDescription("Presentation slides: (Keynote, powerpoint or JPEG)");
            $type->setAllowedExtensions("KEYNOTE|JPG|JPEG|PPT|PPTX");
            $type->markAsSystemDefined();
            $em->persist($type);
        }

        if(!$repository->findOneBy(['name' => "Video (MOV or MP4)"])) {
            $type = new SummitMediaFileType();
            $type->setName("Video (MOV or MP4)");
            $type->setDescription("Video (MOV or MP4)");
            $type->setAllowedExtensions("MOV|MP4");
            $type->markAsSystemDefined();
            $em->persist($type);
        }

        if(!$repository->findOneBy(['name' => "Photoshop PSD file or PDF file"])) {
            $type = new SummitMediaFileType();
            $type->setName("Photoshop PSD file or PDF file");
            $type->setDescription("Photoshop PSD file or PDF file");
            $type->setAllowedExtensions("PSD|PDF");
            $type->markAsSystemDefined();
            $em->persist($type);
        }

        if(!$repository->findOneBy(['name' => "Scalable Vector Graphics file: SVG"])) {
            $type = new SummitMediaFileType();
            $type->setName("Scalable Vector Graphics file: SVG");
            $type->setDescription("Scalable Vector Graphics file: SVG");
            $type->setAllowedExtensions("SVG");
            $type->markAsSystemDefined();
            $em->persist($type);
        }

        $em->flush();
    }
}