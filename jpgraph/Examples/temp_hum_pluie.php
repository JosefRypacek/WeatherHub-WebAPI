<?php

require_once( 'jpgraph/jpgraph.php' );
require_once( 'jpgraph/jpgraph_line.php' );
//require_once( '../jpgraph/jpgraph_date.php' );

setlocale( LC_ALL, "fr_FR" );


$Fichier = file( 'meteo_72h.csv' );
$te_min = 100;
$te_max = -100;

for ( $i = 0; $i < 864; $i++ ) {
  $data = explode( ";", $Fichier[ $i ] );
  /* 
  0 date et heure
  1 pression
  2 temp
  3 hum
  4 rain
  5 rain rate
  6 direction du vent en chiffres
  7 idem en lettres
  8 vent
  9 rafales
  */

  // stockage dates et heures et minutes
  $dt[ $i ] = $data[ 0 ] ; // OK

  // stockage températures
  $te = str_replace( ",", ".", $data[ 2 ] );
  if ( $te < $te_min ) {
    $te_min = $te;
  }
  if ( $te > $te_max ) {
    $te_max = $te;
  }

  if ( $te >= 40 ) {
    $te_40[ $i ] = $te;
    $te_30[ $i ] = 40;
    $te_20[ $i ] = 30;
    $te_10[ $i ] = 20;
    $te_0[ $i ] = 10;
    $te_neg[ $i ] = 0;
  } elseif ( $te >= 30 ) {
    $te_40[ $i ] = 0;
    $te_30[ $i ] = $te;
    $te_20[ $i ] = 30;
    $te_10[ $i ] = 20;
    $te_0[ $i ] = 10;
    $te_neg[ $i ] = 0;
  }
  elseif ( $te >= 20 ) {
    $te_40[ $i ] = 0;
    $te_30[ $i ] = 0;
    $te_20[ $i ] = $te;
    $te_10[ $i ] = 20;
    $te_0[ $i ] = 10;
    $te_neg[ $i ] = 0;
  }
  elseif ( $te >= 10 ) {
    $te_40[ $i ] = 0;
    $te_30[ $i ] = 0;
    $te_20[ $i ] = 0;
    $te_10[ $i ] = $te;
    $te_0[ $i ] = 10;
    $te_neg[ $i ] = 0;
  }
  elseif ( $te > 0 ) {
    $te_40[ $i ] = 0;
    $te_30[ $i ] = 0;
    $te_20[ $i ] = 0;
    $te_10[ $i ] = 0;
    $te_0[ $i ] = $te;
    $te_neg[ $i ] = 0;
  }
  else {
    $te_40[ $i ] = 0;
    $te_30[ $i ] = 0;
    $te_20[ $i ] = 0;
    $te_10[ $i ] = 0;
    $te_0[ $i ] = 0;
    $te_neg[ $i ] = $te;
  }

  // Stockage HUMIDITE
  $he[ $i ] = str_replace( ",", ".", $data[ 3 ] );

  // stockage PLUIE
  $pl[ $i ] = str_replace( ",", ".", $data[ 4 ] );


} // fin de la lecture du fichier

  if ( $te_min > 0 ) {
    $te_min = 0;
  }

$te_min = floor( $te_min - .4 );
$te_max = ceil( $te_max + .4 );

//---------------------------------------------------------------------------
// Create the graph
$graph = new Graph( 860, 400 );

$graph->SetScale( "lin",$te_min,$te_max );
$graph->img->SetMargin( 60, 60, 10, 130 );

$graph->graph_theme = null;

$yt40 = new LinePlot( $te_40 );
$yt40->SetFillColor( '#000' ); //noir
$yt40->SetColor( '#000' );
$graph->Add( $yt40 );

$yt30 = new LinePlot( $te_30 );
$yt30->SetFillColor( '#f00' ); // rouge
$yt30->SetColor( '#f00' );
$graph->Add( $yt30 );

$yt20 = new LinePlot( $te_20 );
$yt20->SetFillColor( '#ffa500' ); // orange
$yt20->SetColor( '#ffa500' );
$graph->Add( $yt20 );

$yt10 = new LinePlot( $te_10 );
$yt10->SetFillColor( '#ffd700' ); // gold
$yt10->SetColor( '#ffd700' );
$graph->Add( $yt10 );

$yt00 = new LinePlot( $te_0 );
$yt00->SetFillColor( '#1e90ff' ); //dodgerblue
$yt00->SetColor( '#1e90ff' );
$graph->Add( $yt00 );

$yneg = new LinePlot( $te_neg );
$yneg->SetFillColor( '#feffff' ); // presque blanc pour éviter la transparence
$yneg->SetColor( '#feffff' );
$graph->Add( $yneg );

//Humidité
$graph->SetY2Scale("lin",0,100);
$yh = new LinePlot($he);
$yh->SetColor('darkblue');
$yh->SetWeight(3);
$graph->AddY2($yh);
$graph->SetY2OrderBack(false);

// axe des X
$graph->xaxis->SetTickLabels($dt);
$graph->xaxis->HideLastTickLabel();

//Set the angle for the labels to 90 degrees
$graph->xaxis->SetLabelAngle( 90 );
$graph->xaxis->SetPos( $te_min );

// The automatic format string for dates can be overridden
//$graph->xaxis->scale->SetDateFormat( 'd/m/Y H:i' );


//transparence
$graph->SetMarginColor( 'white' );
$graph->SetFrame( false );
$graph->ygrid->SetFill( false );

$graph->img->SetTransparent( 'white' );

//font
$graph->title->SetFont( FF_FONT1, FS_BOLD );
$graph->yaxis->SetFont( FF_FONT1, FS_BOLD );
$graph->yaxis->SetFont( FF_FONT1, FS_BOLD );
$graph->xaxis->SetFont( FF_FONT1, FS_BOLD );


// Display the graph
$graph->Stroke();
?>