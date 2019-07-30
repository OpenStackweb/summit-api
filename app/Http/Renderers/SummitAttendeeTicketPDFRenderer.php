<?php namespace App\Http\Renderers;
/**
 * Copyright 2019 OpenStack Foundation
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
use TCPDF;
use models\summit\SummitAttendeeTicket;
/**
 * Class SummitAttendeeTicketPDFRenderer
 * @package App\Http\Renderers
 */
final class SummitAttendeeTicketPDFRenderer implements IRenderer
{
    /**
     * @var SummitAttendeeTicket 
     */
    private $ticket;

    /**
     * SummitAttendeeTicketPDFRenderer constructor.
     * @param SummitAttendeeTicket $ticket
     */
    public function __construct(SummitAttendeeTicket $ticket)
    {
        $this->ticket = $ticket;
    }

    public function render(): string
    {
        $order = $this->ticket->getOrder();
        $summit = $order->getSummit();
        $summit_name = $summit->getName();
        $main_venues = $summit->getMainVenues();
        $price = $this->ticket->getRawCost().' '.$this->ticket->getCurrency();
        $ticket_number = $this->ticket->getNumber();
        $location_name = "";
        if(count($main_venues) > 0){
            $location_name = $main_venues[0]->getName().', '.$main_venues[0]->getFullAddress();
        }
        $order_number = $order->getNumber();
        $dates = $summit->getDatesLabel();
        $owner_full_name = $order->getOwnerFullName();
        $order_creation_date = $order->getCreatedUTC()->format("Y-m-d H:i:s");
        $ticket_type = $this->ticket->getTicketType()->getName();
        $attendee_name =  $this->ticket->hasOwner() ? $this->ticket->getOwner()->getFullName() : 'TBD';
        // create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetTitle($summit_name. ' '.$this->ticket->getNumber());

        // remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // set font
        $pdf->SetFont('helvetica', '', 8);

        // add a page
        $pdf->AddPage();

        $html = view('tickets.raw',[
            'summit_name' => $summit_name,
            'ticket_type' => $ticket_type,
            'price' => $price,
            'location_name' => $location_name,
            'dates' => $dates,
            'order_number' => $order_number,
            'owner_full_name' => $owner_full_name,
            'order_creation_date' => $order_creation_date,
            'attendee_name' => $attendee_name,
        ])->render();

        $pdf->writeHTMLCell(100, 80, 10, 25 , $html, $border=1, $ln=0, $fill=0, $reseth=true, $align='', $autopadding=true);

        $pdf->SetFont('helvetica', '', 5.7);

        // set style for barcode
        $style = [
            'border'        => 2,
            'vpadding'      => 'auto',
            'hpadding'      => 'auto',
            'fgcolor'       => [0, 0, 0],
            'bgcolor'       => false, //array(255,255,255)
            'module_width'  => 1, // width of a single module in points
            'module_height' => 1 // height of a single module in points
        ];

        // QRCODE,L : QR-CODE Low error correction
        $this->ticket->generateQRCode();
        $pdf->write2DBarcode($this->ticket->getQRCode(), 'QRCODE,L', 125, 25, 50, 50, $style, 'N');
        $pdf->Text(124, 85, $ticket_number);

        //Close and output PDF document
        return $pdf->Output($ticket_number.'.pdf', 'S');
    }
}