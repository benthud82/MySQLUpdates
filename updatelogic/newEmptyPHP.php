 <!--Start of slotting detail info-->
                                        <div class="col-md-4">
                                            <div class="portlet sale-summary condensed ">
                                                <h3 class="sub-page-header">Slotting Detail</h3>
                                                <div class="row">
                                                    <div class="portlet-body">

                                                        <div class="col-sm-12 col-md-6 bordered">
                                                            <ul class="list-unstyled">
                                                                <li>
                                                                    <span class="sale-info-condensed">Curr Grid5</span>
                                                                    <span class="sale-num-condensed"><?php echo $displayarray[$key2]['LMGRD5'] ?></span>
                                                                </li>
                                                                <li>
                                                                    <span class="sale-info-condensed">Curr Tier</span>
                                                                    <span class="sale-num-condensed"><?php echo $displayarray[$key2]['LMTIER'] ?></span>
                                                                </li>
                                                                <li>
                                                                    <span class="sale-info-condensed">Curr Max</span>
                                                                    <span class="sale-num-condensed"><?php echo $displayarray[$key2]['CURMAX'] ?></span>
                                                                </li>
                                                                <li>
                                                                    <span class="sale-info-condensed">Avg Daily Qty</span>
                                                                    <span class="sale-num-condensed"><?php echo $displayarray[$key2]['AVG_DAILY_UNIT'] ?></span>
                                                                </li>
                                                                <li>
                                                                    <span class="sale-info-condensed">Imp Moves/Yr</span>
                                                                    <span class="sale-num-condensed"><?php echo intval($displayarray[$key2]['CURRENT_IMPMOVES'] * 253) ?></span>
                                                                </li>
                                                                <li>
                                                                    <span class="sale-info-condensed">Optimal Bay</span>
                                                                    <span class="sale-num-condensed"><?php echo intval($displayarray[$key2]['OPT_OPTBAY']) ?></span>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                        <div class="col-sm-12 col-md-6">
                                                            <ul class="list-unstyled">
                                                                <li>
                                                                    <span class="sale-info-condensed">New Grid5</span>
                                                                    <span class="sale-num-condensed"><?php echo $displayarray[$key2]['SUGGESTED_GRID5'] ?></span>
                                                                </li>
                                                                <li>
                                                                    <span class="sale-info-condensed">New Tier</span>
                                                                    <span class="sale-num-condensed"><?php echo $displayarray[$key2]['SUGGESTED_TIER'] ?></span>
                                                                </li>
                                                                <li>
                                                                    <span class="sale-info-condensed">New Slot Qty</span>
                                                                    <span class="sale-num-condensed"><?php echo $displayarray[$key2]['SUGGESTED_SLOTQTY'] ?></span>
                                                                </li>
                                                                <li>
                                                                    <span class="sale-info-condensed">Avg Daily Picks</span>
                                                                    <span class="sale-num-condensed"><?php echo $displayarray[$key2]['AVG_DAILY_PICK'] ?></span>
                                                                </li>
                                                                <li>
                                                                    <span class="sale-info-condensed">New Moves/Yr</span>
                                                                    <span class="sale-num-condensed"><?php echo intval($displayarray[$key2]['SUGGESTED_IMPMOVES'] * 253) ?></span>
                                                                </li>
                                                                <li>
                                                                    <span class="sale-info-condensed">Current Bay</span>
                                                                    <span class="sale-num-condensed"><?php echo intval($displayarray[$key2]['OPT_CURRBAY']) ?></span>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!--End of slotting detail info-->
                                        
                                        
                                        
                                        
                                        
                                        
                                        
                                        
                                        
                                        
                                        
                                        
                                        
                                        
                                        
                                        
                                        
                                        
                                        
                                        
                                        
                                        
                                        
                                        
                                        
                                        
                                        
                                        
                                        
                                        
                                        
                                        
                                        
                                        
                                        
                                        
                                        
                                        
                                        
                                        
                                        
                                        
                                        






    <section class="panel"> 
        <header class="panel-heading bg bg-inverse h2"> Total Average Score Increase: <?php echo number_format($avg_score_inc * 100, 2) . '%' ?> </header>
        <ul class="list-group"> 
            <?php foreach ($displayarray as $key2 => $value2) { ?> 
                <!--Start of main display-->
                <li class="list-group-item" > 
                    <div class="media-body">
                        <div class="row">
                            <div class="col-sm-3 bordered text-center" style="padding-bottom: 5px;">
                                <div class="col-sm-12 h3" style="padding-bottom: 5px;"> <?php echo $displayarray[$key2]['ITEM_NUMBER'] ?></div> 
                                <div class="col-sm-12 text-muted h5" style="padding-bottom: 0px;">Item Code</div>
                            </div>
                            <div class="col-sm-3 bordered text-center" style="padding-bottom: 5px;">
                                <div class="col-sm-12 h3" style="padding-bottom: 5px;"> <?php echo $displayarray[$key2]['CUR_LOCATION'] ?></div> 
                                <div class="col-sm-12 text-muted h5" style="padding-bottom: 0px;">Location</div>
                            </div>
                            <div class="col-sm-3 bordered text-center" style="padding-bottom: 5px;">
                                <div class="col-sm-12 h3" style="padding-bottom: 5px;"> <?php echo $displayarray[$key2]['RecText'] ?></div> 
                                <div class="col-sm-12 text-muted h5" style="padding-bottom: 0px;">Recommendation</div>
                            </div>
                            <div class="col-sm-3 text-center" style="padding-bottom: 5px;">
                                <div class="col-sm-12 h3" style="padding-bottom: 5px;"> <?php
                                    if ($displayarray[$key2]['FinalSavings'] == 'N/A') {
                                        echo 'N/A';
                                    } else {
                                        echo number_format($displayarray[$key2]['FinalSavings'] * 100, 2) . '%';
                                    }
                                    ?><i class="fa fa-chevron-circle-down clicktotoggle-chevron" style="float: right; cursor: pointer;"></i></div> 
                                <div class="col-sm-12 text-muted h5" style="padding-bottom: 0px;">Score Increase</div>
                            </div>
                        </div>
                    </div>


                    <!--End of main display-->
                    <!--Start of hidden detail display-->

                    <div class="hiddencostdetail"  style="display: none; padding: 30px 0px 30px 0px;">
                        <div class="row"> 
                            <div class="col-sm-12">
                                <div class="portlet solid grey-cararra">
                                    <!-- STAT -->
                                    <div class="row list-separated profile-stat" style=" padding-top: 25px;">
                                        <!--Start of what to do by reslot number-->
                                        <div class="col-sm-4 bordered">
                                            <?php include 'reslotdetailbynumber.php'; // include file to determine detail how to obtain cost savings based on returned number from $finalrecommendation array from the _reslotrecommendation in the slottingfunctions.php file             ?>
                                        </div>
                                        <!--End of what to do by reslot number-->

                                        <!--Start of Cost Savings info-->
                                        <div class="col-sm-4 bordered" style="margin-bottom: 20px;">
                                            <?php include 'costsavingsdetailbynumber.php'; // include file to determine detail how to obtain cost savings based on returned number from $finalrecommendation array from the _reslotrecommendation in the slottingfunctions.php file             ?>
                                        </div>
                                        <!--End of Cost Savings info-->

                                        <!--Start of slotting detail info-->
                                        <div class="col-sm-4 " style="margin-bottom: 20px;">
                                            <div class="row">
                                                <div class="col-sm-12 col-md-6 bordered nopadding_bottom">
                                                    <div class="media-body"> 
                                                        <div class="media-mini text-center"> 
                                                            <strong class="h4 pull-left">Curr. Grid5: </strong>
                                                            <a href="" class="h4 pull-right bold"><?php echo ' ' . $displayarray[$key2]['LMGRD5'] ?></a> 
                                                        </div> 

                                                    </div>
                                                </div> 
                                                <div class="col-sm-12 col-md-6 bordered nopadding_bottom">
                                                    <div class="media-body"> 
                                                        <div class="media-mini text-center"> 
                                                            <strong class="h4 pull-left">Curr. Grid5: </strong>
                                                            <a href="" class="h4 pull-right bold"><?php echo ' ' . $displayarray[$key2]['LMGRD5'] ?></a> 
                                                        </div> 

                                                    </div>
                                                </div> 
                                                <div class="col-sm-12 col-md-6 bordered nopadding_bottom">
                                                    <div class="media-body"> 
                                                        <div class="media-mini text-center"> 
                                                            <strong class="h4 pull-left">Curr. Grid5: </strong>
                                                            <a href="" class="h4 pull-right bold"><?php echo ' ' . $displayarray[$key2]['LMGRD5'] ?></a> 
                                                        </div> 

                                                    </div>
                                                </div> 
                                                <div class="col-sm-12 col-md-6 bordered nopadding_bottom">
                                                    <div class="media-body"> 
                                                        <div class="media-mini text-center"> 
                                                            <strong class="h4 pull-left">Curr. Grid5: </strong>
                                                            <a href="" class="h4 pull-right bold"><?php echo ' ' . $displayarray[$key2]['LMGRD5'] ?></a> 
                                                        </div> 

                                                    </div>
                                                </div> 
                                                <div class="col-sm-12 col-md-6 bordered nopadding_bottom">
                                                    <div class="media-body"> 
                                                        <div class="media-mini text-center"> 
                                                            <strong class="h4 pull-left">Curr. Grid5: </strong>
                                                            <a href="" class="h4 pull-right bold"><?php echo ' ' . $displayarray[$key2]['LMGRD5'] ?></a> 
                                                        </div> 

                                                    </div>
                                                </div> 
                                                <div class="col-sm-12 col-md-6 bordered nopadding_bottom">
                                                    <div class="media-body"> 
                                                        <div class="media-mini text-center"> 
                                                            <strong class="h4 pull-left">Curr. Grid5: </strong>
                                                            <a href="" class="h4 pull-right bold"><?php echo ' ' . $displayarray[$key2]['LMGRD5'] ?></a> 
                                                        </div> 

                                                    </div>
                                                </div> 
                                                <div class="col-sm-12 col-md-6 bordered nopadding_bottom">
                                                    <div class="media-body"> 
                                                        <div class="media-mini text-center"> 
                                                            <strong class="h4 pull-left">Curr. Grid5: </strong>
                                                            <a href="" class="h4 pull-right bold"><?php echo ' ' . $displayarray[$key2]['LMGRD5'] ?></a> 
                                                        </div> 

                                                    </div>
                                                </div> 
                                                <div class="col-sm-12 col-md-6 bordered nopadding_bottom">
                                                    <div class="media-body"> 
                                                        <div class="media-mini text-center"> 
                                                            <strong class="h4 pull-left">Curr. Grid5: </strong>
                                                            <a href="" class="h4 pull-right bold"><?php echo ' ' . $displayarray[$key2]['LMGRD5'] ?></a> 
                                                        </div> 

                                                    </div>
                                                </div> 
                                                <div class="col-sm-12 col-md-6 bordered nopadding_bottom">
                                                    <div class="media-body"> 
                                                        <div class="media-mini text-center"> 
                                                            <strong class="h4 pull-left">Curr. Grid5: </strong>
                                                            <a href="" class="h4 pull-right bold"><?php echo ' ' . $displayarray[$key2]['LMGRD5'] ?></a> 
                                                        </div> 

                                                    </div>
                                                </div> 
                                                <div class="col-sm-12 col-md-6 bordered nopadding_bottom">
                                                    <div class="media-body"> 
                                                        <div class="media-mini text-center"> 
                                                            <strong class="h4 pull-left">Curr. Grid5: </strong>
                                                            <a href="" class="h4 pull-right bold"><?php echo ' ' . $displayarray[$key2]['LMGRD5'] ?></a> 
                                                        </div> 

                                                    </div>
                                                </div> 
                                                <div class="col-sm-12 col-md-6 bordered nopadding_bottom">
                                                    <div class="media-body"> 
                                                        <div class="media-mini text-center"> 
                                                            <strong class="h4 pull-left">Curr. Grid5: </strong>
                                                            <a href="" class="h4 pull-right bold"><?php echo ' ' . $displayarray[$key2]['LMGRD5'] ?></a> 
                                                        </div> 

                                                    </div>
                                                </div> 
                                                <div class="col-sm-12 col-md-6 bordered nopadding_bottom">
                                                    <div class="media-body"> 
                                                        <div class="media-mini text-center"> 
                                                            <strong class="h4 pull-left">Curr. Grid5: </strong>
                                                            <a href="" class="h4 pull-right bold"><?php echo ' ' . $displayarray[$key2]['LMGRD5'] ?></a> 
                                                        </div> 

                                                    </div>
                                                </div> 
                                                <div class="col-sm-12 col-md-6 bordered nopadding_bottom">
                                                    <div class="media-body"> 
                                                        <div class="media-mini text-center"> 
                                                            <strong class="h4 pull-left">Curr. Grid5: </strong>
                                                            <a href="" class="h4 pull-right bold"><?php echo ' ' . $displayarray[$key2]['LMGRD5'] ?></a> 
                                                        </div> 

                                                    </div>
                                                </div> 
                                                <div class="col-sm-12 col-md-6 bordered nopadding_bottom">
                                                    <div class="media-body"> 
                                                        <div class="media-mini text-center"> 
                                                            <strong class="h4 pull-left">Curr. Grid5: </strong>
                                                            <a href="" class="h4 pull-right bold"><?php echo ' ' . $displayarray[$key2]['LMGRD5'] ?></a> 
                                                        </div> 

                                                    </div>
                                                </div> 

                                            </div> 
                                        </div> 
                                    </div>
                                    <!--End of slotting detail info-->
                                </div>
                                <!-- END STAT -->
                            </div>
                        </div>
                    </div>
                </li>
                <?php } ?>
            </ul>
        </section>

    <!--End of hidden detail display-->












