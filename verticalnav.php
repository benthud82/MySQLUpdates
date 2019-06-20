<?php
if (isset($_SESSION['MYUSER'])) {
    $var_userid = strtoupper($_SESSION['MYUSER']);
} else {
    $var_userid = NULL;
}

//pull in warning badges
include 'globaldata/opentasksbadge.php'; //$badgecount for open tasks assigned to TSM
include 'globaldata/maperrorbadge.php'; //$maperrorcount for vector mapping errors

$totalmoduleerrors = $maperrorcount;  //+add+ $slotmastererror once programmed
?>

<!--Need to create separate php page for nav bar-->
<nav id="nav" class="nav-primary hidden-xs nav-vertical" style="position: fixed;"> 
    <ul class="nav" data-spy="" data-offset-top="50"> 
        <li id="dash"><a href="dashboard.php"><i class="fa fa-dashboard fa-lg"></i><span>Dashboard</span></a></li> 
        <li id="loose"><a href="looseslotting.php"><i class="fa fa-stack-overflow fa-lg"></i><span>Loose<br>Slotting</span></a></li> 
        <li id="case"><a href="caseslotting.php"><i class="fa fa-cubes fa-lg"></i><span>Case<br>Slotting</span></a></li> 
        <li id="reports" class="dropdown-submenu" style="cursor: pointer;"> <a ><i class="fa fa-th fa-lg"></i><span>Reports</span></a> 

            <ul class="dropdown-menu" style="min-width: 600px"> 
                <div class="row">
                    <div class="col-sm-6">
                        <h4 style="cursor: default; margin: 3px 3px 3px 3px">Item Specific Reports</h4>
                        <li><a href="itemquery.php">Item Query</a></li> 
                        <li><a href="itemhistory.php">Item History</a></li> 
                        <li><a href="movesdetail.php">Move Detail</a></li> 
                        <li><a href="shortsdetail.php">Shorts Detail</a></li> 
                        <li><a href="moveassist.php">Move Assist</a></li> 
                    </div>
                    <div class="col-sm-6">
                        <h4 style="cursor: default; margin: 3px 0px 3px 3px;">Re-slot Reports</h4>
                        <li><a href="optimalbay.php">Optimal Bay</a></li> 
                        <li><a href="casehightolow.php">Case High to Low</a></li> 
                        <li><a href="buildingswap.php">Building Swap (Sparks)</a></li> 
                        <li><a href="maxminadjustment.php">Max/Min Adjustment</a></li> 
                        <li><a href="walktime.php">Walk Times</a></li> 
                        <li><a href="dogpound.php">Dog Pound</a></li> 
                        <li><a href="needswants.php">Needs / Wants</a></li> 
                        <li><a href="caseopps.php">Case Pkgu Opps</a></li> 
                        <li><a href="ipopps.php">IP Pkgu Opps</a></li> 
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-6">
                        <h4 style="cursor: default; margin: 3px 3px 3px 3px;" class="">Maintenance Reports</h4>
                        <li><a href="itemsonhold.php">Items on Hold</a></li> 
                        <li><a href="emptylocations.php">Empty Locations</a></li> 
                        <li><a href="smallcaseops.php">Small Case Opps</a></li> 
                        <li><a href="flowrestricted.php">Flow Restricted</a></li> 
                    </div>
                    <div class="col-sm-6">
                        <h4 style="cursor: default; margin: 3px 3px 3px 3px;" class="">New Item Reports</h4>
                        <li><a href="slotrec.php">Slot Recommendation</a></li> 
                        <li><a href="newitemaudit.php">Audit</a></li> 
                        <li><a href="truefitcalc.php">True Fit Calc</a></li> 
                        <li><a href="Instructions.php">Instructions</a></li> 
                        <li><a href="ReferenceManual.php">Reference Manual</a></li> 
                        <li><a href="itemchangelog.php">Item Change Log</a></li> 
                        <li><a href="changelogPFRsettings.php">PFR Change Log</a></li> 
                        <li><a href="changelogmediasettings.php">OK in Media Log</a></li> 
                    </div>
                </div>
            </ul> 

        </li>
        <li id="heatmap"><a href="heatmap.php"><i class="fa fa-map fa-lg"></i><span>Heat Map</span></a></li> 
        <!--<li id="help"><a href="help.php"><i class="fa fa-question fa-lg"></i><span>Help / FAQ</span></a></li>--> 

        <li id="taskmanager"><a href="taskmanager.php"><?php
                if ($badgecount > 0) {
                    echo "<b class='badge bg-danger pull-right'>$badgecount</b>";
                }
                ?><i class="fa fa-tasks fa-lg"></i><span>Tasks</span></a></li> 
        <li id="modules" class="dropdown-submenu" style="cursor: pointer;"><a >
                <?php
                if ($totalmoduleerrors > 0) {
                    echo "<b class='badge bg-danger pull-right'>$totalmoduleerrors</b>";
                }
                ?>
                <i class="fa fa-cogs fa-lg"></i><span>Modules</span></a>

            <ul class="dropdown-menu"> 
                <li><a href="changelog.php">Change Log</a></li> 
                <li><a href="2018Goal.php">2018 Goal</a></li> 
                <?php if ($var_userid == 'BHUD01' || $var_userid == 'JMOO01') { ?>
                    <li><a href="vectormap.php">Vector Map</a></li> 
                    <li><a href = "maperrors.php">Map Errors
                            <?php
                            if ($maperrorcount > 0) {
                                echo "<b class='badge bg-danger pull-right'>$maperrorcount</b>";
                            }
                            ?>
                        </a></li>
                <?php } ?>


            </ul>
        </li>

<!--        <li id="dash"><a href="help.php"><i class="fa fa-question-circle fa-lg"></i><span>Help</span></a></li> -->
    </ul> 
</nav>
