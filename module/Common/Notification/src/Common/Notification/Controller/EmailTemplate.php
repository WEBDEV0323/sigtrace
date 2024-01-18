<?php

namespace Common\Notification\Controller;

class EmailTemplate
{
    protected $msg;

    // public function __construct($msg)
    // {
    //     $this->msg = $msg;
    // }

    public function emailTemplate($msg, $groupId, $logo)
    {
        //        //$path = $logo;
        //        $type = pathinfo($logo, PATHINFO_EXTENSION);
        //        $data = file_get_contents($logo);
        $year=date("Y");
        $endsection = '<br><br><div class="col-md-6">For any questions please email us back at   '.$groupId.' </div><br><br><div class="col-md-6">Regards, </div><div class="col-md-6">Bioclinica SigTRACE Team</div>';
        $htmlPart = '<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
            <head>
            <meta charset="UTF-8">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>Email Template</title>
            <style type="text/css">
            p{
            margin:10px 0;
            padding:0;
            }
            table{
            border-collapse:collapse;
            }
            h1,h2,h3,h4,h5,h6{
            display:block;
            margin:0;
            padding:0;
            }
            img,a img{
            border:0;
            height:auto;
            outline:none;
            text-decoration:none;
            }
            body,#bodyTable,#bodyCell{
            height:100%;
            margin:0;
            padding:0;
            width:100%;
            }
            .mcnPreviewText{
            display:none !important;
            }
            #outlook a{
            padding:0;
            }
            img{
            -ms-interpolation-mode:bicubic;
            }
            table{
            mso-table-lspace:0pt;
            mso-table-rspace:0pt;
            }
            .ReadMsgBody{
            width:100%;
            }
            .ExternalClass{
            width:100%;
            }
            p,a,li,td,blockquote{
            mso-line-height-rule:exactly;
            }
            a[href^=tel],a[href^=sms]{
            color:inherit;
                cursor:default;
                text-decoration:none;
            }
            p,a,li,td,body,table,blockquote{
                -ms-text-size-adjust:100%;
                -webkit-text-size-adjust:100%;
            }
            .ExternalClass,.ExternalClass p,.ExternalClass td,.ExternalClass div,.ExternalClass span,.ExternalClass font{
                line-height:100%;
            }
            a[x-apple-data-detectors]{
                color:inherit !important;
                text-decoration:none !important;
                font-size:inherit !important;
                font-family:inherit !important;
                font-weight:inherit !important;
                line-height:inherit !important;
            }
            #bodyCell{
                padding:10px;
            }
            .templateContainer{
                max-width:600px !important;
            }
            a.mcnButton{
                display:block;
            }
            .mcnImage{
                vertical-align:bottom;
            }
            .mcnTextContent{
                word-break:break-word;
            }
            .mcnTextContent img{
                height:auto !important;
            }
            .mcnDividerBlock{
                table-layout:fixed !important;
            }
            /*
            @tab Page
            @section Background Style
            @tip Set the background color and top border for your email. You may want to choose colors that match your companys branding.
            */
            body,#bodyTable{
                /*@editable*/background-color:#FFFFFF;
                /*@editable*/background-image:none;
                /*@editable*/background-repeat:no-repeat;
                /*@editable*/background-position:center;
                /*@editable*/background-size:cover;
            }
            /*
            @tab Page
            @section Background Style
            @tip Set the background color and top border for your email. You may want to choose colors that match your companys branding.
            */
            #bodyCell{
                /*@editable*/border-top:0;
            }
            /*
            @tab Page
            @section Email Border
            @tip Set the border for your email.
            */
            .templateContainer{
                /*@editable*/border:0;
            }
            /*
            @tab Page
            @section Heading 1
            @tip Set the styling for all first-level headings in your emails. These should be the largest of your headings.
            @style heading 1
            */
            h1{
                /*@editable*/color:#202020;
                /*@editable*/font-family:Helvetica;
                /*@editable*/font-size:26px;
                /*@editable*/font-style:normal;
                /*@editable*/font-weight:bold;
                /*@editable*/line-height:125%;
                /*@editable*/letter-spacing:normal;
                /*@editable*/text-align:left;
            }
            /*
            @tab Page
            @section Heading 2
            @tip Set the styling for all second-level headings in your emails.
            @style heading 2
            */
            h2{
                /*@editable*/color:#202020;
                /*@editable*/font-family:Helvetica;
                /*@editable*/font-size:22px;
                /*@editable*/font-style:normal;
                /*@editable*/font-weight:bold;
                /*@editable*/line-height:125%;
                /*@editable*/letter-spacing:normal;
                /*@editable*/text-align:left;
            }
            /*
            @tab Page
            @section Heading 3
            @tip Set the styling for all third-level headings in your emails.
            @style heading 3
            */
            h3{
                /*@editable*/color:#202020;
                /*@editable*/font-family:Helvetica;
                /*@editable*/font-size:20px;
                /*@editable*/font-style:normal;
                /*@editable*/font-weight:bold;
                /*@editable*/line-height:125%;
                /*@editable*/letter-spacing:normal;
                /*@editable*/text-align:left;
            }
            /*
            @tab Page
            @section Heading 4
            @tip Set the styling for all fourth-level headings in your emails. These should be the smallest of your headings.
            @style heading 4
            */
            h4{
                /*@editable*/color:#202020;
                /*@editable*/font-family:Helvetica;
                /*@editable*/font-size:18px;
                /*@editable*/font-style:normal;
                /*@editable*/font-weight:bold;
                /*@editable*/line-height:125%;
                /*@editable*/letter-spacing:normal;
                /*@editable*/text-align:left;
            }
            /*
            @tab Header
            @section Header Style
            @tip Set the borders for your emails header area.
            */
            #templateHeader{
                /*@editable*/border-top:0;
                /*@editable*/border-bottom:0;
            }
            /*
            @tab Header
            @section Header Text
            @tip Set the styling for your emails header text. Choose a size and color that is easy to read.
            */
            #templateHeader .mcnTextContent,#templateHeader .mcnTextContent p{
                /*@editable*/color:#202020;
                /*@editable*/font-family:Helvetica;
                /*@editable*/font-size:16px;
                /*@editable*/line-height:150%;
                /*@editable*/text-align:left;
            }
            /*
            @tab Header
            @section Header Link
            @tip Set the styling for your emails header links. Choose a color that helps them stand out from your text.
            */
            #templateHeader .mcnTextContent a,#templateHeader .mcnTextContent p a{
                /*@editable*/color:#2BAADF;
                /*@editable*/font-weight:normal;
                /*@editable*/text-decoration:underline;
            }
            /*
            @tab Body
            @section Body Style
            @tip Set the borders for your emails body area.
            */
            #templateBody{
                /*@editable*/border-top:0;
                /*@editable*/border-bottom:0;
            }
            /*
            @tab Body
            @section Body Text
            @tip Set the styling for your emails body text. Choose a size and color that is easy to read.
            */
            #templateBody .mcnTextContent,#templateBody .mcnTextContent p{
                /*@editable*/color:#202020;
                /*@editable*/font-family:Helvetica;
                /*@editable*/font-size:16px;
                /*@editable*/line-height:150%;
                /*@editable*/text-align:left;
            }
            /*
            @tab Body
            @section Body Link
            @tip Set the styling for your emails body links. Choose a color that helps them stand out from your text.
            */
            #templateBody .mcnTextContent a,#templateBody .mcnTextContent p a{
                /*@editable*/color:#2BAADF;
                /*@editable*/font-weight:normal;
                /*@editable*/text-decoration:underline;
            }
            /*
            @tab Footer
            @section Footer Style
            @tip Set the borders for your emails footer area.
            */
            #templateFooter{
                /*@editable*/border-top:0;
                /*@editable*/border-bottom:0;
            }
            /*
            @tab Footer
            @section Footer Text
            @tip Set the styling for your emails footer text. Choose a size and color that is easy to read.
            */
            #templateFooter .mcnTextContent,#templateFooter .mcnTextContent p{
                /*@editable*/color:#202020;
                /*@editable*/font-family:Helvetica;
                /*@editable*/font-size:12px;
                /*@editable*/line-height:150%;
                /*@editable*/text-align:left;
            }
            /*
            @tab Footer
            @section Footer Link
            @tip Set the styling for your emails footer links. Choose a color that helps them stand out from your text.
            */
            #templateFooter .mcnTextContent a,#templateFooter .mcnTextContent p a{
                /*@editable*/color:#202020;
                /*@editable*/font-weight:normal;
                /*@editable*/text-decoration:underline;
            }
            @media only screen and (min-width:768px) {
            .templateContainer{
                width:600px !important;
            }

            }	@media only screen and (max-width: 480px) {
            body,table,td,p,a,li,blockquote{
                -webkit-text-size-adjust:none !important;
            }

            }	@media only screen and (max-width: 480px) {
            body{
                width:100% !important;
                min-width:100% !important;
            }

            }	@media only screen and (max-width: 480px) {
            #bodyCell{
                padding-top:10px !important;
            }

            }	@media only screen and (max-width: 480px) {
            .mcnImage{
                width:100% !important;
            }

            }	@media only screen and (max-width: 480px) {
            .mcnCartContainer,.mcnCaptionTopContent,.mcnRecContentContainer,.mcnCaptionBottomContent,.mcnTextContentContainer,.mcnBoxedTextContentContainer,.mcnImageGroupContentContainer,.mcnCaptionLeftTextContentContainer,.mcnCaptionRightTextContentContainer,.mcnCaptionLeftImageContentContainer,.mcnCaptionRightImageContentContainer,.mcnImageCardLeftTextContentContainer,.mcnImageCardRightTextContentContainer{
                max-width:100% !important;
                width:100% !important;
            }

            }	@media only screen and (max-width: 480px) {
            .mcnBoxedTextContentContainer{
                min-width:100% !important;
            }

            }	@media only screen and (max-width: 480px) {
            .mcnImageGroupContent{
                padding:9px !important;
            }

            }	@media only screen and (max-width: 480px) {
            .mcnCaptionLeftContentOuter .mcnTextContent,.mcnCaptionRightContentOuter .mcnTextContent{
                padding-top:9px !important;
            }

            }	@media only screen and (max-width: 480px) {
            .mcnImageCardTopImageContent,.mcnCaptionBlockInner .mcnCaptionTopContent:last-child .mcnTextContent{
                padding-top:18px !important;
            }

            }	@media only screen and (max-width: 480px) {
            .mcnImageCardBottomImageContent{
                padding-bottom:9px !important;
            }

            }	@media only screen and (max-width: 480px) {
            .mcnImageGroupBlockInner{
                padding-top:0 !important;
                padding-bottom:0 !important;
            }

            }	@media only screen and (max-width: 480px) {
            .mcnImageGroupBlockOuter{
                padding-top:9px !important;
                padding-bottom:9px !important;
            }

            }	@media only screen and (max-width: 480px) {
            .mcnTextContent,.mcnBoxedTextContentColumn{
                padding-right:18px !important;
                padding-left:18px !important;
            }

            }	@media only screen and (max-width: 480px) {
            .mcnImageCardLeftImageContent,.mcnImageCardRightImageContent{
                padding-right:18px !important;
                padding-bottom:0 !important;
                padding-left:18px !important;
            }

            }	@media only screen and (max-width: 480px) {
            .mcpreview-image-uploader{
                display:none !important;
                width:100% !important;
            }

            }	@media only screen and (max-width: 480px) {
            /*
            @tab Mobile Styles
            @section Heading 1
            @tip Make the first-level headings larger in size for better readability on small screens.
            */
            h1{
                /*@editable*/font-size:22px !important;
                /*@editable*/line-height:125% !important;
            }

            }	@media only screen and (max-width: 480px) {
            /*
            @tab Mobile Styles
            @section Heading 2
            @tip Make the second-level headings larger in size for better readability on small screens.
            */
            h2{
                /*@editable*/font-size:20px !important;
                /*@editable*/line-height:125% !important;
            }

            }	@media only screen and (max-width: 480px) {
            /*
            @tab Mobile Styles
            @section Heading 3
            @tip Make the third-level headings larger in size for better readability on small screens.
            */
            h3{
                /*@editable*/font-size:18px !important;
                /*@editable*/line-height:125% !important;
            }

            }	@media only screen and (max-width: 480px) {
            /*
            @tab Mobile Styles
            @section Heading 4
            @tip Make the fourth-level headings larger in size for better readability on small screens.
            */
            h4{
                /*@editable*/font-size:16px !important;
                /*@editable*/line-height:150% !important;
            }

            }	@media only screen and (max-width: 480px) {
            /*
            @tab Mobile Styles
            @section Boxed Text
            @tip Make the boxed text larger in size for better readability on small screens. We recommend a font size of at least 16px.
            */
            table.mcnBoxedTextContentContainer td.mcnTextContent,td.mcnBoxedTextContentContainer td.mcnTextContent p{
                /*@editable*/font-size:14px !important;
                /*@editable*/line-height:150% !important;
            }

            }	@media only screen and (max-width: 480px) {
            /*
            @tab Mobile Styles
            @section Header Text
            @tip Make the header text larger in size for better readability on small screens.
            */
            td#templateHeader td.mcnTextContent,td#templateHeader td.mcnTextContent p{
                /*@editable*/font-size:16px !important;
                /*@editable*/line-height:150% !important;
            }

            }	@media only screen and (max-width: 480px) {
            /*
            @tab Mobile Styles
            @section Body Text
            @tip Make the body text larger in size for better readability on small screens. We recommend a font size of at least 16px.
            */
            td#templateBody td.mcnTextContent,td#templateBody td.mcnTextContent p{
                /*@editable*/font-size:16px !important;
                /*@editable*/line-height:150% !important;
            }

            }	@media only screen and (max-width: 480px) {
            /*
            @tab Mobile Styles
            @section Footer Text
            @tip Make the footer content text larger in size for better readability on small screens.
            */
            td#templateFooter td.mcnTextContent,td#templateFooter td.mcnTextContent p{
                /*@editable*/font-size:14px !important;
                /*@editable*/line-height:150% !important;
            }

            }
            </style>
            </head>

                        <body style="background-color: #fafafa;">
                        <span class="mcnPreviewText" style="display:none; font-size:0px; line-height:0px; max-height:0px; max-width:0px; opacity:0; overflow:hidden; visibility:hidden; mso-hide:all;"></span><!--<![endif]-->
            <!--*|END:IF|*-->
            <center>
                <table align="center" border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" id="bodyTable">
                    <tr>
                        <td align="left" valign="top" id="bodyCell">
                            <!-- BEGIN TEMPLATE // -->
                            <!--[if gte mso 9]>
                            <table align="center" border="0" cellspacing="0" cellpadding="0" width="600" style="width:600px;">
                            <tr>
                            <td align="center" valign="top" width="600" style="width:600px;">
                            <![endif]-->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" class="templateContainer">
                                <tr>
                                    <td valign="top" id="templateHeader"><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnImageBlock" style="min-width:100%;">
            <tbody class="mcnImageBlockOuter">
                <tr>
                    <td valign="top" style="padding:9px" class="mcnImageBlockInner">
                        <table align="left" width="100%" border="0" cellpadding="0" cellspacing="0" class="mcnImageContentContainer" style="min-width:100%;">
                            <tbody><tr>
                                <td class="mcnImageContent" valign="top" style="padding-right: 9px; padding-left: 9px; padding-top: 0; padding-bottom: 0;">


                            <img align="left" alt="" src="https://gallery.mailchimp.com/b8c1bf751511076c3b3fbd5b5/images/98d31b48-89fa-4eae-aa05-1dc535b66ec2.png" width="100" style="max-width:200px; padding-bottom: 0; display: inline !important; vertical-align: bottom;" class="mcnImage">


                                </td>

                            </tr>
                        </tbody></table>
                    </td>
                </tr>
            </tbody>
            </table>
            <table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnDividerBlock" style="min-width:100%;">
            <tbody class="mcnDividerBlockOuter">
            <tr>
                <td class="mcnDividerBlockInner" style="min-width: 100%; padding: 5px 18px;">
                    <table class="mcnDividerContent" border="0" cellpadding="0" cellspacing="0" width="100%" style="min-width: 100%;border-top: 2px solid #EAEAEA;">
                        <tbody><tr>
                            <td>
                                <span></span>
                            </td>
                        </tr>
                    </tbody></table>
            <!--
                    <td class="mcnDividerBlockInner" style="padding: 18px;">
                    <hr class="mcnDividerContent" style="border-bottom-color:none; border-left-color:none; border-right-color:none; border-bottom-width:0; border-left-width:0; border-right-width:0; margin-top:0; margin-right:0; margin-bottom:0; margin-left:0;" />
            -->
                </td>
            </tr>
            </tbody>
            </table></td>
            </tr>
            <tr bgcolor="#ffffff">
                            <td bgcolor="#ffffff" style="padding:5px 10px;">'.$msg.$endsection.'</td>
                        </tr>

            </table><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnDividerBlock" style="min-width:100%;">
            <tbody class="mcnDividerBlockOuter">
            <tr>
                <td class="mcnDividerBlockInner" style="min-width: 100%; padding: 5px 18px;">
                    <table class="mcnDividerContent" border="0" cellpadding="0" cellspacing="0" width="100%" style="min-width: 100%;border-top: 2px solid #EAEAEA;">
                        <tbody><tr>
                            <td>
                                <span></span>
                            </td>
                        </tr>
                    </tbody></table>
            <!--
                    <td class="mcnDividerBlockInner" style="padding: 18px;">
                    <hr class="mcnDividerContent" style="border-bottom-color:none; border-left-color:none; border-right-color:none; border-bottom-width:0; border-left-width:0; border-right-width:0; margin-top:0; margin-right:0; margin-bottom:0; margin-left:0;" />
            -->
                </td>
            </tr>
            </tbody>
            </table></td>
            </tr>
                                <tr>
            <td valign="top" id="templateFooter"><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnBoxedTextBlock" style="min-width:100%;">
            <!--[if gte mso 9]>
            <table align="center" border="0" cellspacing="0" cellpadding="0" width="100%">
            <![endif]-->
            <tbody class="mcnBoxedTextBlockOuter">
            <tr>
                <td valign="top" class="mcnBoxedTextBlockInner">

                    <!--[if gte mso 9]>
                    <td align="center" valign="top" ">
                    <![endif]-->
                    <table align="left" border="0" cellpadding="0" cellspacing="0" width="100%" style="min-width:100%;" class="mcnBoxedTextContentContainer">
                        <tbody><tr>

                            <td style="padding-top:9px; padding-left:18px; padding-bottom:9px; padding-right:18px;">

                                <table border="0" cellpadding="18" cellspacing="0" class="mcnTextContentContainer" width="100%" style="min-width: 100% !important;background-color: #404040;border: 1px solid;">
                                    <tbody><tr>
                                        <td valign="top" class="mcnTextContent" style="color: #F2F2F2;font-family: Helvetica;font-size: 13px;font-weight: normal;line-height: 100%;text-align: left;">
                                            <em>&copy; '.$year.' <a style="color: #00FFFF;" href="http://www.bioclinica.com">Bioclinica</a>. All rights reserved.&nbsp;</em>Privacy &amp; Legal
                                        </td>
                                    </tr>
                                </tbody></table>
                            </td>
                        </tr>
                    </tbody></table>
                    <!--[if gte mso 9]>
                    </td>
                    <![endif]-->

                    <!--[if gte mso 9]>
                    </tr>
                    </table>
                    <![endif]-->
                </td>
            </tr>
            </tbody>
            </table></td>
                                </tr>
                            </table>
                            <!--[if gte mso 9]>
                            </td>
                            </tr>
                            </table>
                            <![endif]-->
                            <!-- // END TEMPLATE -->
                        </td>
                    </tr>
                </table>
                        <tr style="height: 5px;">
                            <td></td>
                        </tr>
                        </table>
                        </body>
                        </html>';
            return $htmlPart;
    }
}
?>
