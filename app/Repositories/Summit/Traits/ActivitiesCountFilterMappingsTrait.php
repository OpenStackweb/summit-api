<?php namespace App\Repositories\Summit\Traits;
/*
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

use App\Http\Utils\Filters\SQL\SQLRawFilterMapping;
use App\Http\Utils\Filters\SQL\SQLSwitchFilterMapping;
use models\summit\SummitSelectedPresentation;
use models\summit\SummitSelectedPresentationList;
use utils\Filter;

/**
 * Trait ActivitiesCountFilterMappingsTrait
 *
 * Phase-2 filter mappings for the speakers/submitters activities count.
 *
 * Those endpoints resolve WHICH people match the request filter in phase 1 (the DQL
 * conditions of each repository's getFilterMappings), then count the presentations of
 * those people in phase 2. Phase 2 needs the same presentation-level conditions, only
 * expressed against the physical presentation row rather than against the person, or a
 * filter such as has_published_presentations counts the unpublished presentations of the
 * matched speakers too.
 *
 * Person-level filters (id, not_id, first_name, last_name, email, full_name, member_id,
 * member_user_external_id, is_speaker) are absent on purpose: they only decide who
 * matches. Filter::toRawSQL skips whatever it has no mapping for, which leaves the count
 * unrestricted for them, and both repositories share this list because the conditions
 * correlate to the presentation, not to the role the person plays on it.
 *
 * Known limitation, inherited from Filter::toRawSQL and shared with every other caller of
 * it: skipping an unmapped field is right for a slot joined by AND, but inside an OR group
 * it drops a branch instead of widening it, so `full_name==x,presentations_track_id==N`
 * counts only the track N presentations even though phase 1 also matched people through
 * full_name. Expressing the correct rule needs a per-person predicate, which no set-level
 * condition can carry. The behaviour is pinned by
 * testActivitiesCountWithAnOredPersonLevelFilterKeepsThePresentationBranch in both
 * repository test suites.
 *
 * @package App\Repositories\Summit\Traits
 */
trait ActivitiesCountFilterMappingsTrait
{
    /**
     * Turns the request filter into the phase-2 WHERE fragment and the bindings the
     * statement needs.
     *
     * Contract for the caller: the statement selects from `SummitEvent E` joined to
     * `Presentation P`, binds the summit as `:summit_id`, and appends the fragment to its
     * own WHERE. The fragment is empty when no presentation-level filter applies.
     *
     * @param Filter|null $filter
     * @param int $summit_id
     * @return array [string $extra_filters, array $bindings]
     */
    protected function buildActivitiesCountFilter(?Filter $filter, int $summit_id): array
    {
        $extra_filters = '';
        $bindings = ['summit_id' => $summit_id];

        if (!is_null($filter)) {
            $where = $filter->toRawSQL($this->getActivitiesCountFilterMappings());
            if (!empty($where)) {
                $extra_filters = ' AND (' . $where . ')';
                $bindings = array_merge($bindings, $filter->getSQLBindings());
            }
        }

        return [$extra_filters, $bindings];
    }

    /**
     * Aliases the conditions correlate to: SummitEvent E, Presentation P.
     *
     * The selection-status semantics are copied from the phase-1 DQL mappings, not
     * re-derived. PresentationMediaUpload is a JOINED subclass of PresentationMaterial,
     * so its PresentationID column lives on the parent table and the type on the child.
     *
     * @return array
     */
    private function getActivitiesCountFilterMappings(): array
    {
        return [
            'presentations_track_id' => new SQLRawFilterMapping(
                'E.CategoryID :operator :value'
            ),
            'presentations_track_group_id' => new SQLRawFilterMapping(
                'EXISTS (
                     SELECT 1
                     FROM PresentationCategoryGroup_Categories __cg
                     WHERE __cg.PresentationCategoryID = E.CategoryID
                         AND __cg.PresentationCategoryGroupID :operator :value
                 )'
            ),
            'presentations_selection_plan_id' => new SQLRawFilterMapping(
                'P.SelectionPlanID :operator :value'
            ),
            'presentations_type_id' => new SQLRawFilterMapping(
                'E.TypeID :operator :value'
            ),
            'presentations_title' => new SQLRawFilterMapping(
                'LOWER(E.Title) :operator LOWER(:value)'
            ),
            'presentations_abstract' => new SQLRawFilterMapping(
                'LOWER(E.Abstract) :operator LOWER(:value)'
            ),
            'presentations_submitter_full_name' => new SQLRawFilterMapping(
                "EXISTS (
                     SELECT 1
                     FROM `Member` __sub
                     WHERE __sub.ID = E.CreatedByID
                         AND CONCAT(LOWER(__sub.FirstName), ' ', LOWER(__sub.Surname)) :operator LOWER(:value)
                 )"
            ),
            'presentations_submitter_email' => new SQLRawFilterMapping(
                'EXISTS (
                     SELECT 1
                     FROM `Member` __sub
                     WHERE __sub.ID = E.CreatedByID
                         AND LOWER(__sub.Email) :operator LOWER(:value)
                 )'
            ),
            'has_media_upload_with_type' => new SQLRawFilterMapping(
                'EXISTS (
                     SELECT 1
                     FROM PresentationMediaUpload __mu
                     INNER JOIN PresentationMaterial __mat ON __mat.ID = __mu.ID
                     WHERE __mat.PresentationID = E.ID
                         AND __mu.SummitMediaUploadTypeID :operator :value
                 )'
            ),
            'has_not_media_upload_with_type' => new SQLRawFilterMapping(
                'NOT EXISTS (
                     SELECT 1
                     FROM PresentationMediaUpload __mu
                     INNER JOIN PresentationMaterial __mat ON __mat.ID = __mu.ID
                     WHERE __mat.PresentationID = E.ID
                         AND __mu.SummitMediaUploadTypeID :operator :value
                 )'
            ),
            // The == false side of every status filter must not restrict the count: a
            // person matched by it has no presentation with that status among the ones
            // that pass the remaining presentation-level filters, so all of them qualify.
            'has_published_presentations' => new SQLSwitchFilterMapping([
                'true' => 'E.Published = 1',
                'false' => SQLSwitchFilterMapping::NoRestriction,
            ]),
            'has_accepted_presentations' => new SQLSwitchFilterMapping([
                // accepted = selected within the track session count, or published
                'true' => sprintf(
                    'EXISTS (
                         SELECT 1
                         FROM SummitSelectedPresentation __sp
                         INNER JOIN SummitSelectedPresentationList __spl ON __spl.ID = __sp.SummitSelectedPresentationListID
                         INNER JOIN PresentationCategory __cat ON __cat.ID = E.CategoryID
                         WHERE __sp.PresentationID = E.ID
                             AND __sp.Collection = \'%1$s\'
                             AND __spl.ListType = \'%2$s\'
                             AND __spl.ListClass = \'%3$s\'
                             AND __sp.`Order` IS NOT NULL
                             AND __sp.`Order` <= __cat.SessionCount
                     ) OR E.Published = 1',
                    SummitSelectedPresentation::CollectionSelected,
                    SummitSelectedPresentationList::Group,
                    SummitSelectedPresentationList::Session
                ),
                'false' => SQLSwitchFilterMapping::NoRestriction,
            ]),
            'has_alternate_presentations' => new SQLSwitchFilterMapping([
                // alternate = selected beyond the track session count
                'true' => sprintf(
                    'EXISTS (
                         SELECT 1
                         FROM SummitSelectedPresentation __sp
                         INNER JOIN SummitSelectedPresentationList __spl ON __spl.ID = __sp.SummitSelectedPresentationListID
                         INNER JOIN PresentationCategory __cat ON __cat.ID = E.CategoryID
                         WHERE __sp.PresentationID = E.ID
                             AND __sp.Collection = \'%1$s\'
                             AND __spl.ListType = \'%2$s\'
                             AND __spl.ListClass = \'%3$s\'
                             AND __sp.`Order` IS NOT NULL
                             AND __sp.`Order` > __cat.SessionCount
                     )',
                    SummitSelectedPresentation::CollectionSelected,
                    SummitSelectedPresentationList::Group,
                    SummitSelectedPresentationList::Session
                ),
                'false' => SQLSwitchFilterMapping::NoRestriction,
            ]),
            'has_rejected_presentations' => new SQLSwitchFilterMapping([
                // rejected = not published and absent from every Group/Session list,
                // the order playing no part, as in the phase-1 mapping
                'true' => sprintf(
                    'E.Published = 0 AND NOT EXISTS (
                         SELECT 1
                         FROM SummitSelectedPresentation __sp
                         INNER JOIN SummitSelectedPresentationList __spl ON __spl.ID = __sp.SummitSelectedPresentationListID
                         WHERE __sp.PresentationID = E.ID
                             AND __sp.Collection = \'%1$s\'
                             AND __spl.ListType = \'%2$s\'
                             AND __spl.ListClass = \'%3$s\'
                     )',
                    SummitSelectedPresentation::CollectionSelected,
                    SummitSelectedPresentationList::Group,
                    SummitSelectedPresentationList::Session
                ),
                'false' => SQLSwitchFilterMapping::NoRestriction,
            ]),
        ];
    }
}
