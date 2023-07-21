<?php namespace App\Jobs\Emails;
/**
 * Copyright 2023 OpenStack Foundation
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

/**
 * Interface IMailTemplatesConstants
 * @package App\Jobs\Emails
 */
interface IMailTemplatesConstants
{
    const accepted_moderated_presentations = 'accepted_moderated_presentations';
    const accepted_presentations = 'accepted_presentations';
    const admin_ticket_edit_url = 'admin_ticket_edit_url';
    const alternate_moderated_presentations = 'alternate_moderated_presentations';
    const alternate_presentations = 'alternate_presentations';
    const approval_date = 'approval_date';
    const aprover_email = 'aprover_email';
    const aprover_fullname = 'aprover_fullname';
    const attachments = 'attachments';
    const bio_edit_link = 'bio_edit_link';
    const candidate_email = 'candidate_email';
    const candidate_full_name = 'candidate_full_name';
    const candidate_has_accepted_nomination = 'candidate_has_accepted_nomination';
    const candidate_nominations_count = 'candidate_nominations_count';
    const cc_email = 'cc_email';
    const code = 'code';
    const content = 'content';
    const confirmation_number = 'confirmation_number';
    const creator_email = 'creator_email';
    const creator_full_name = 'creator_full_name';
    const currency = 'currency';
    const currency_symbol = 'currency_symbol';
    const description = 'description';
    const discount_amount = 'discount_amount';
    const discount_rate = 'discount_rate';
    const disposition = 'disposition';
    const election_app_deadline = 'election_app_deadline';
    const election_title = 'election_title';
    const error_message = 'error_message';
    const event_date = 'event_date';
    const event_description = 'event_description';
    const event_title = 'event_title';
    const event_uri = 'event_uri';
    const event_url = 'event_url';
    const external_id = 'external_id';
    const email_to = 'email_to';
    const feed_type = 'feed_type';
    const first_name = 'first_name';
    const from_email = 'from_email';
    const has_owner = 'has_owner';
    const hash = 'hash';
    const invitation_token = 'invitation_token';
    const is_discount = 'is_discount';
    const last_name = 'last_name';
    const link = 'link';
    const magic_link = 'magic_link';
    const manage_orders_url = 'manage_orders_url';
    const member_id = 'member_id';
    const message = 'message';
    const name = 'name';
    const need_details = 'need_details';
    const net_selling_price = 'net_selling_price';
    const new_category = 'new_category';
    const number = 'number';
    const old_category = 'old_category';
    const order_amount = 'order_amount';
    const order_amount_adjusted = 'order_amount_adjusted';
    const order_credit_card_type = 'order_credit_card_type';
    const order_credit_card_4number = 'order_credit_card_4number';
    const order_currency = 'order_currency';
    const order_currency_symbol = 'order_currency_symbol';
    const order_discount = 'order_discount';
    const order_id = 'order_id';
    const order_number = 'order_number';
    const order_owner_company = 'order_owner_company';
    const order_owner_email = 'order_owner_email';
    const order_owner_full_name = 'order_owner_full_name';
    const order_qr_value = 'order_qr_value';
    const order_raw_amount = 'order_raw_amount';
    const order_refunded_amount = 'order_refunded_amount';
    const order_taxes = 'order_taxes';
    const owner_company = 'owner_company';
    const owner_email = 'owner_email';
    const owner_first_name = 'owner_first_name';
    const owner_fullname = 'owner_fullname';
    const owner_full_name = 'owner_full_name';
    const owner_last_name = 'owner_last_name';
    const presentation_edit_link = 'presentation_edit_link';
    const presentation_end_date = 'presentation_end_date';
    const presentation_id = 'presentation_id';
    const presentation_name = 'presentation_name';
    const presentation_location = 'presentation_location';
    const presentation_start_date = 'presentation_start_date';
    const presentation_title = 'presentation_title';
    const price = 'price';
    const promo_code = 'promo_code';
    const promo_code_discount_amount = 'promo_code_discount_amount';
    const promo_code_discount_rate = 'promo_code_discount_rate';
    const promo_code_until_date = 'promo_code_until_date';
    const reason = 'reason';
    const raw_summit_marketing_site_url = 'raw_summit_marketing_site_url';
    const raw_summit_virtual_site_url = 'raw_summit_virtual_site_url';
    const registration_link = 'registration_link';
    const rejected_moderated_presentations = 'rejected_moderated_presentations';
    const rejected_presentations = 'rejected_presentations';
    const report = 'report';
    const requested_by_email = 'requested_by_email';
    const requested_by_full_name = 'requested_by_full_name';
    const requester_email = 'requester_email';
    const requester_fullname = 'requester_fullname';
    const reservation_amount = 'reservation_amount';
    const reservation_currency = 'reservation_currency';
    const reservation_created_datetime = 'reservation_created_datetime';
    const reservation_end_datetime = 'reservation_end_datetime';
    const reservation_id = 'reservation_id';
    const reservation_refunded_amount = 'reservation_id';
    const reservation_start_datetime = 'reservation_start_datetime';
    const reset_password_link = 'reset_password_link';
    const review_link = 'review_link';
    const room_capacity = 'room_capacity';
    const room_complete_name = 'room_complete_name';
    const selection_plan_id = 'selection_plan_id';
    const selection_plan_name = 'selection_plan_name';
    const selection_plan_submission_end_date = 'selection_plan_submission_end_date';
    const selection_plan_submission_start_date = 'selection_plan_submission_start_date';
    const selection_process_link = 'selection_process_link';
    const set_password_link = 'set_password_link';
    const set_password_link_to_registration = 'set_password_link_to_registration';
    const speaker_confirmation_link = 'speaker_confirmation_link';
    const speaker_email = 'speaker_email';
    const speaker_full_name = 'speaker_full_name';
    const speaker_management_link = 'speaker_management_link';
    const status = 'status';
    const submitter_email = 'submitter_email';
    const submitter_full_name = 'submitter_full_name';
    const submitter_fullname = 'submitter_fullname';
    const summit_date = 'summit_date';
    const summit_dates_label = 'summit_dates_label';
    const summit_id = 'summit_id';
    const summit_logo = 'summit_logo';
    const summit_marketing_site_url = 'summit_marketing_site_url';
    const summit_marketing_site_oauth2_client_id = 'summit_marketing_site_oauth2_client_id';
    const summit_marketing_site_oauth2_scopes = 'summit_marketing_site_oauth2_scopes';
    const summit_name = 'summit_name';
    const summit_reassign_ticket_till_date = 'summit_reassign_ticket_till_date';
    const summit_schedule_url = 'summit_schedule_url';
    const summit_site_url = 'summit_site_url';
    const summit_schedule_default_event_detail_url = 'summit_schedule_default_event_detail_url';
    const summit_virtual_site_oauth2_client_id = 'summit_virtual_site_oauth2_client_id';
    const summit_virtual_site_url = 'summit_virtual_site_url';
    const support_email = 'support_email';
    const tenant_name = 'tenant_name';
    const ticket_amount = 'ticket_amount';
    const ticket_currency = 'ticket_currency';
    const ticket_currency_symbol = 'ticket_currency_symbol';
    const ticket_discount = 'ticket_discount';
    const ticket_id = 'ticket_id';
    const ticket_number = 'ticket_number';
    const ticket_owner = 'ticket_owner';
    const ticket_promo_code = 'ticket_promo_code';
    const ticket_raw_amount = 'ticket_raw_amount';
    const ticket_refund_amount = 'ticket_refund_amount';
    const ticket_refund_status = 'ticket_refund_status';
    const ticket_taxes = 'ticket_taxes';
    const ticket_type = 'ticket_type';
    const ticket_type_name = 'ticket_type_name';
    const ticket_types = 'ticket_types';
    const tickets = 'tickets';
    const to_email = 'to_email';
    const token = 'token';
    const track = 'track';
    const track_id = 'track_id';
    const type = 'type';
    const until_date = 'until_date';
    const virtual_event_site_link = 'virtual_event_site_link';
}