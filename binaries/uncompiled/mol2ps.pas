program mol2ps;
(*
mol2ps/mol2svg
Norbert Haider, University of Vienna, 2005-2014
norbert.haider@univie.ac.at

with code contributions by
Michael Palmer, University of Waterloo

This software is published under the terms of the GNU General Public
License (GPL, see below). For a detailed description of this license,
see http://www.gnu.org/copyleft/gpl.html

This program reads chemical structure files in MDL molfile format 
and generates high-quality postscript output of the 2D structures. 
Starting with version 0.2, the program processes also reaction files 
in MDL rxn and rdf format. Starting with version 0.2a, SVG (scalable 
vectors graphics) is supported as an alternative output format. Starting
from version 0.3, SVG support is enabled by default. To make use of this 
SVG support, just rename (or copy/hard-link) the mol2ps executable into 
"mol2svg" (for Windows: "mol2svg.exe").

The resulting postscript graphics can then be printed or converted 
into various bitmap formats, using the well-known Ghostscript software.

For a more detailed description, please visit
http://merian.pch.univie.ac.at/~nhaider/cheminf/mol2ps.html

To a large extent, code of the GPL program, checkmol/matchmol, is
reused, for more information please visit the checkmol/matchmol
homepage at
http://merian.pch.univie.ac.at/~nhaider/cheminf/cmmm.html



Compile with fpc (Free Pascal, see http://www.freepascal.org), using
the -Sd or -S2 option (Delphi mode; IMPORTANT!)

example for compilation (with optimization) and installation:

fpc -S2 -O3 mol2ps.pas

as "root", do the following:

cp mol2ps /usr/local/bin/
ln /usr/local/bin/mol2ps /usr/local/bin/mol2svg
ln /usr/local/bin/mol2ps /usr/local/bin/mol2eps

Note: do NOT use symbolic links ("ln -s mol2ps mol2svg"), as this will not work!


Version history

v0.1   basic functionality;

v0.1a  slight adjustments of H positioning

v0.1b  further adjustments of H positioning; print H if bond
       is marked "up" or "down" (new option --hydrogenonstereo)

v0.1c  added bond type 'C' for complex bonds (a dashed line),
       bug fix in printPS2DdoubleN()

v0.1d  added support for colored atom labels: new option
       --color=/path/to/color.conf (a simple ascii file with
       4 columns, containing the element symbol and RGB values
       as integers from 0 to 255, space-separated); added support
       for isotopes and radicals; fixed crash when 2 atoms have 
       identical XYZ coordinates in combination with certain
       bond types

v0.1e  minor change in representation of isothiocyanates etc.
       (now shows C for carbon); added missing interpretation for 
       "--autoscale=" option
       
v0.1f  minor bug fix in printPSdouble(), printPStriple(), printPSchars,
       write_PS_bonds_and_boxes; added rudimentary support for brackets 
       around (sub)structures; added some debug output; ; added some 
       debug output; merging of some more CSEARCH-related functionality

v0.2   added support for reactions (MDL rxn and rdf file formats)

v0.2a  minor bug fixes; added rudimentary support for SVG (scalable 
       vector graphics) output (just rename the mol2ps executable into 
       mol2svg; disabled by default); added some support for Sgroups

v0.2b  minor bug fixes; added support for deprecated "A   nnn" atom aliases;
       refined SVG output of labels
       
v0.3   changed SVG output to a more compact format; SVG support is no 
       longer a compile-time option, but included by default; minor bug 
       fixes; added SVG comments which enable re-adjustment of width, 
       height and viewbox dimensions       

v0.3a  added --showmaps parameter: displays atom-atom mapping numbers in
       red color (useful only for reactions with atom-atom mapping)

v0.4   added EPS output option; use buffered output in order to include 
       correct %%BoundingBox and SVG viewbox dimensions (based on a code 
       contribution by Michael Palmer, University of Waterloo); added new 
       command-line options (--output=, --bgcolor=, --scaling=)

v0.4a  fixed a minor color problem in PS mode; fixed a "comma vs. decimal
       point" issue in all format() calls (i.e., now forcing decimal point
       as decimal separator, independently of locale settings)
       
v0.4b  minor change in center_mol (relevant for FlaME-generated rxnfiles);

===============================================================================
DISCLAIMER
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software Foundation,
Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
===============================================================================
*)

//{$DEFINE debug}               // uncomment this to enable the -D option
{$DEFINE csearch_extensions}  // v0.1c (complex bond encoded by MDL "stereo" = 4)
//{$DEFINE clean_marvin_r}      // converts all atoms of type "R#" into "R"


{$NOTES OFF}
{$WARNINGS OFF}

uses
  SYSUTILS, MATH, STRUTILS, CLASSES;


const
  version       = '0.4b';
  max_atoms     = 1024;
  max_bonds     = 1024;
  max_ringsize  = 128;
  max_rings     = 1024;
  max_neighbors = 20;    // was 16 in v0.1x
  max_brackets  = 64;    // v0.1f
  max_sgroups   = 512;   // v0.2a
  TAB           = #26;
  max_rgbentries = 128;  // v0.1d

  rs_sar        = 2001;  // ring search mode: SAR = set of all rings
  rs_ssr        = 2002;  //                   SSR = set of small rings

  btopo_any        = 0;  // bond topology
  btopo_ring       = 1;  //
  btopo_chain      = 2;  //
  btopo_always_any = 3;  // even in "strict mode"
  btopo_excess_rc  = 4;  // bond in query and candidate must have same ring count
  btopo_exact_rc   = 5;  // bond in candidate must be included in _more_ rings than
                         // the matching bond in the query ==> specific search for
                         // annulated systems

  bstereo_any      = 0;  // any E/Z isomer (for double bonds)
  bstereo_xyz      = 1;  // E/Z match is checked by using XYZ coordinates of the atoms
  bstereo_up       = 11; // flags for single bonds
  bstereo_down     = 16; //

  //================constants
  blfactor       = 75;  // relative bond length
  PX             = 1.25;

  dir_right      = 1;
  dir_rightup    = 2;
  dir_up         = 3;
  dir_leftup     = 4;
  dir_left       = 5;
  dir_leftdown   = 6;
  dir_down       = 7;
  dir_rightdown  = 8;
  dirtolerance   = 5;  // degrees

  defaultfontname  = 'Helvetica';
  defaultfontsize1 = 14;
  defaultfontsize2 = 9;
  defaultlinewidth = 1.0;  
  rgbfilename : string = 'color.conf';
  max_recursion_depth  = 500000;

  pmMol2PS     = 2001;
  pmMol2SVG   = 2002;
  svg_factor  = 0.21;  // was 0.24 initially
  dd          = 1; // decimal digits for SVG XY coordinates

type
  str2  = string[2];
  str3  = string[3];
  str4  = string[4];
  str5  = string[5];
  str8  = string[8];
  str80 = string[80];
  atom_rec  = record
                element : str2;
                atype : str3;
                x : single;
                y : single;
                z : single;
                x_orig : single;
                y_orig : single;
                z_orig : single;
                formal_charge : integer;
                real_charge : single;
                Hexp : smallint;  // explicit H count
                Htot : smallint;  // total H count
                neighbor_count : integer;
                ring_count : integer;
                arom : boolean;
                stereo_care : boolean;
	        heavy : boolean;         
		metal : boolean;         
                nvalences : integer;
                tag : boolean;
                hidden : boolean;
                nucleon_number : integer;
                radical_type : integer;
                sg : boolean; // v0.2a
                alias : str80;   // v0.2b
                a_just : smallint;  // v0.2b;  0: left, 1: right, 2: center
                map_id : integer;  // v0.3a
             end;
  bond_rec  = record
                a1 : integer;
                a2 : integer;
                btype : char;
                bsubtype : char;
                a_handle : integer;
                ring_count : integer;
                arom : boolean;
                topo : shortint;   // see MDL file description
                stereo : shortint;
                mdl_stereo : shortint; // new in v0.c
                drawn : boolean;
                hidden : boolean;
                sg : boolean; // v0.2a
              end;
  rgb_record = record
                  element : str2;
                  r : integer;
                  g : integer;
                  b : integer;
               end;
  ringpath_type = array[1..max_ringsize] of integer;

  atomlist      = array[1..max_atoms] of atom_rec;
  bondlist      = array[1..max_bonds] of bond_rec;
  ringlist      = array[1..max_rings] of ringpath_type;
  neighbor_rec  = array[1..max_neighbors] of integer;
  molbuftype    = array[1..(max_atoms+max_bonds+8192)] of string;

  ringprop_rec  = record
                    size     : integer;
                    arom     : boolean;
                    envelope : boolean;
                  end;

  ringprop_type = array[1..max_rings] of ringprop_rec;
  p_3d          = record
                    x : double;
                    y : double;
                    z : double;
                  end;

  bracket_rec   = record  // v0.1f
                    id : integer;
                    x1, y1, x2, y2, x3, y3, x4, y4 : single;
                    brtype : integer;
                    brlabel : string;
                  end;
                  
  bracket_type  = array[1..max_brackets] of bracket_rec;  // v0.1f

  sgroup_rec    = record  // v0.2a, for all sgroups other than brackets (SRU)
                    id : integer;
                    sgtype : string;
                    anchor : integer;
                    justification : char;
                    sglabel : string;
                    x : single;
                    y : single;
                  end;

  sgroup_type   = array[1..max_sgroups] of sgroup_rec;  // v0.2a
  attr_arr      = array[1..80] of byte;  // v0.2b
  
var
  progmode      : integer;
  progname      : string;
  li            : longint;
  ln            : longint;

  rxn_mode      : boolean;  // v0.2
  n_reactants   : integer;  // v0.2
  n_products    : integer;  // v0.2
  x_shift       : single;   // v0.2
  x_padding     : single;   // v0.2
  y_margin      : single;   // v0.2
  x_min         : single;   // v0.2
  x_max         : single;   // v0.2
  x_dummy       : single;   // v0.2
  arrow_length  : single;   // v0.2
  i             : integer;
  
  opt_stdin     : boolean;
  opt_debug     : boolean;
  opt_metalrings: boolean;
  opt_rs        : integer;

  opt_autoscale   : boolean;
  opt_autorotate    : boolean;
  opt_autorotate3Donly : boolean;
  opt_stripH      : boolean;
  opt_Honhetero   : boolean;
  opt_Honmethyl   : boolean;
  opt_Honstereo   : boolean;
  opt_showmolname : boolean;
  opt_atomnum     : boolean;
  opt_bondnum     : boolean;
  opt_color       : boolean;
  opt_sgroups     : boolean;  // v0.2a
  opt_maps        : boolean;  // v0.3a
  opt_bgcolor     : boolean;
  opt_eps         : boolean;

  filetype : string;
  molfile : text;
  molfilename : string;
  molname : string;
  //molcomment  : string;
  n_atoms : integer;
  n_bonds : integer;
  n_rings : integer;    // the number of rings we determined ourselves
  n_heavyatoms : integer;
  n_heavybonds : integer;
  n_brackets   : integer;  // v0.1f
  n_sgroups    : integer;  // v0.2a

  atom : ^atomlist;
  bond : ^bondlist;
  ring : ^ringlist;
  ringprop : ^ringprop_type;
  bracket  : ^bracket_type;  // v0.1f
  sgroup  : ^sgroup_type;  // v0.2a

  atomtype : str4;
  newatomtype : str3;

  molbuf : ^molbuftype;
  molbufindex : integer;

  mol_in_queue : boolean;
  mol_count    : longint;
  rxn_count    : longint;  // v0.2

  ringsearch_mode : integer;
  max_vringsize   : integer;  // for SSR ring search

  rfile : text;
  rfile_is_open : boolean;
  mol_OK        : boolean;
  n_ar          : integer;
  prev_n_ar     : integer;

  maxY : double;
  xoffset, yoffset : single;
  std_bondlength : double;  
  db_spacingfactor : double;
  std_blCCsingle : double;
  std_blCCdouble : double;
  std_blCCarom   : double;
  fontname  : string;
  fontsize1 : integer;
  fontsize2 : integer;
  zorder    : array[1..max_atoms] of integer;
  xrot, yrot, zrot : double;
  linewidth : double;
  sf_mol    : double;
  lblmargin : single;
  rgbtable : array[1..max_rgbentries] of rgb_record;
  rgbfile : text;
  recursion_level : longint;
  //recursion_depth : longint;
  svg_yoffset : integer;
  svg_mode    : integer;  // v0.2c; 1 = full (use a <line> tag for each bond), 2 = compact (use <path>)
  svg_max_x, svg_max_y, svg_min_y : single;  // v0.2c; needed for adjustment of width and height parameters
  ytrans : integer;  // v0.2c
  max_ytrans : integer; // v0.2c
  outbuffer : TStringList;  // v0.4
  global_scaling : single; // v0.4
  bgcolor : rgb_record;  // v0.4
  bgrgbstr : string;     // v0.4
  bboxleft, bboxright, bboxbottom, bboxtop      : integer;
  dotscale, bboxmargin    : single;
  bbleft_int, bbright_int, bbtop_int, bbbottom_int : integer;  // v0.4
  ymargin : integer;  // v0.4
  fsettings : TFormatSettings;  // v0.4a
  
//================= auxiliary functions & procedures

procedure init_globals;
var
  i : integer;
begin
  opt_debug       := false;
  opt_stdin       := false;
  opt_metalrings  := false;
  opt_stripH      := true;
  opt_Honhetero   := true;
  opt_Honmethyl   := true;
  opt_Honstereo   := true;
  opt_autoscale   := true;
  opt_autorotate  := true;
  opt_autorotate3dOnly := false;
  opt_showmolname := false;
  opt_atomnum     := false;
  opt_bondnum     :=false;
  opt_color       := false;
  opt_sgroups     := true;  // v0.2a
  opt_maps        := false; // v0.3a
  opt_bgcolor     := false; // v0.4
  std_blCCsingle  := 1.541;
  std_blCCdouble  := 1.337;
  std_blCCarom    := 1.394;
  std_bondlength  := std_blCCdouble;
  db_spacingfactor:= 0.18;
  fontname  := 'Helvetica';
  fontsize1 := defaultfontsize1;
  fontsize2 := defaultfontsize2;
  linewidth := defaultlinewidth;
  xrot      := 0;
  yrot      := 0;
  zrot      := 0;
  sf_mol    := 1.0;
  lblmargin := 0.8;
  opt_rs          := rs_sar;
  ringsearch_mode := rs_sar;
  rfile_is_open   := false;
  try
    getmem(molbuf,sizeof(molbuftype));
  except
    on e:Eoutofmemory do
      begin
        writeln('Not enough memory');
        halt(4);
      end;
  end;
  for i := 1 to max_rgbentries do
    begin
      rgbtable[i].element := '';
      rgbtable[i].r := 0; rgbtable[i].g := 0; rgbtable[i].b := 0;
    end;
  n_brackets   := 0;       // v0.1f
  n_sgroups    := 0;       // v0.2a
  rxn_mode     := false;   // v0.2
  ln           := 0;       // v0.2
  x_padding    := 1.8;     // v0.2
  y_margin     := 1.0;     // v0.2
  arrow_length := 4.0;     // v0.2
  svg_yoffset  := 300;     // v0.2a    maybe this needs some dynamic adjustment 
  svg_mode     := 1;       // v0.2c    use "full" as default, "compact" only for really flat molecules
  svg_max_x    := -10000;  // v0.2c
  svg_max_y    := -10000;  // v0.2c
  svg_min_y    := 10000;   // v0.2c
  max_ytrans   := 0;       // v0.2c
  global_scaling := 1.0;   // v0.4
  bgcolor.r    := 255;
  bgcolor.g    := 255;
  bgcolor.b    := 255;
  // far out starting values for bounding box, will be updated when drawing
  bboxleft        := 2000;
  bboxright       := 0;
  bboxtop         := 0;
  bboxbottom      := 2000;
  bboxmargin      := 2.0;
  dotscale        := 0.24;  // to keep the scaling of bbox and dot in sync  
  fsettings.decimalseparator := '.';  // v0.4a force decimal point for floating point numbers
end;

function get_stringwidth(const fs:integer; const tstr:string):double;
// returns a relative string width for proportional fonts (as a very crude value)
var
  res, cw : double;
  i,l : integer;
  c : char;
begin
  res := 0;
  l := length(tstr);
  if (l > 0) then
    begin
      for i := 1 to l do
        begin
          cw := 1.0;
          c := tstr[i];
          if (pos(c,'iIl1.,:;!()[]')>0) then cw := 0.4;
          if (pos(c,'mMwW')>0) then cw := 1.7;
          res := res + cw;
        end;
    end;
  res := res * fs;  // fs = font size
  get_stringwidth := res;
end;

procedure writeout(const fmt: string; const args: array of const);
begin
  outbuffer.append(format(fmt, args,fsettings));
end;

procedure writeouts(const stuff: string);
begin
  outbuffer.append(stuff)
end;


procedure updatebb(const x: integer; const y: integer);
begin
  if x < bboxleft then bboxleft := x;
  if x > bboxright then bboxright := x;
  if y < bboxbottom then bboxbottom := y;
  if y > bboxtop then bboxtop := y;
end;

procedure calc_PSboundingbox;
begin
  bbleft_int   := round(global_scaling*dotscale*bboxleft)   - round(bboxmargin*defaultfontsize1);
  bbbottom_int := round(global_scaling*dotscale*bboxbottom) - round(0.75*bboxmargin*defaultfontsize1);
  bbright_int  := round(global_scaling*dotscale*bboxright)  + round(bboxmargin*defaultfontsize1);
  bbtop_int    := round(global_scaling*dotscale*bboxtop)    + round(0.75*bboxmargin*defaultfontsize1);
end;

procedure write_PSboundingbox;
begin
  writeln('%%BoundingBox: ',bbleft_int,' ',bbbottom_int,' ',bbright_int,' ',bbtop_int);
end;

procedure write_PSbg;
begin
  writeln;
  writeln('% use a background as specified by the --bgcolor option');
  writeln(bgrgbstr,' setrgbcolor');
  writeln('newpath ',bbleft_int,' ',bbbottom_int,' moveto');
  writeln(bbright_int,' ',bbbottom_int,' lineto');
  writeln(bbright_int,' ',bbtop_int,' lineto');
  writeln(bbleft_int,' ',bbtop_int,' lineto');
  writeln(bbleft_int,' ',bbbottom_int,' lineto');
  writeln('closepath fill');
  writeln('0 0 0 setrgbcolor');
end;

procedure debugoutput(dstr:string);
begin
  if opt_debug then
    begin
      if (progmode = pmMol2PS)  then writeouts('%% '+dstr);  // v0.1b; added "%" (Postscript comment line)
      if (progmode = pmMol2SVG) then writeouts('<!-- '+dstr+' -->');
    end;
end;


procedure left_trim(var trimstr:string);
begin
  while (length(trimstr)>0) and ((trimstr[1]=' ') or (trimstr[1]=TAB)) do delete(trimstr,1,1);
end;


function left_int(var trimstr:string):integer;
var
  numstr : string;
  auxstr : string;
  auxint, code : integer;
begin
  numstr := '-+0123456789';
  auxstr := '';
  auxint := 0;
  while (length(trimstr)>0) and ((trimstr[1]=' ') or (trimstr[1]=TAB)) do
    delete(trimstr,1,1);
  while (length(trimstr)>0) and (pos(trimstr[1],numstr)>0) do
    begin
      auxstr := auxstr + trimstr[1];
      delete(trimstr,1,1);
    end;
  val(auxstr,auxint,code);
  if (code <> 0) then auxint := 0;
  left_int := auxint;
end;


function left_float(var trimstr:string):single;  // v0.1f
var
  numstr : string;
  auxstr : string;
  auxfloat : single;
  code : integer;
begin
  numstr := '-+0123456789.';
  auxstr := '';
  auxfloat := 0;
  while (length(trimstr)>0) and ((trimstr[1]=' ') or (trimstr[1]=TAB)) do
    delete(trimstr,1,1);
  while (length(trimstr)>0) and (pos(trimstr[1],numstr)>0) do
    begin
      auxstr := auxstr + trimstr[1];
      delete(trimstr,1,1);
    end;
  val(auxstr,auxfloat,code);
  if (code <> 0) then auxfloat := 0;
  left_float := auxfloat;
end;


//============================= geometry functions ==========================

function dist3d(p1,p2:p_3d):double;
var
  res : double;
begin
  res    := sqrt(sqr(p1.x-p2.x) + sqr(p1.y-p2.y) + sqr(p1.z-p2.z));
  dist3d := res;
end;


function subtract_3d(p1,p2:p_3d):p_3d;
var
  p : p_3d;
begin
  p.x := p1.x - p2.x;
  p.y := p1.y - p2.y;
  p.z := p1.z - p2.z;
  subtract_3d := p;
end;


function add_3d(p1,p2:p_3d):p_3d;
var
  p : p_3d;
begin
  p.x := p1.x + p2.x;
  p.y := p1.y + p2.y;
  p.z := p1.z + p2.z;
  add_3d := p;
end;


procedure vec2origin(var p1,p2:p_3d);
var
  p : p_3d;
begin
  p := subtract_3d(p2,p1);
  p2 := p;
  p1.x := 0; p1.y := 0; p1.z := 0;
end;


function scalar_prod(p1,p2,p3:p_3d):double;
var
  p : p_3d;
  res : double;
begin
  p := subtract_3d(p2,p1);
  p2 := p;
  p := subtract_3d(p3,p1);
  p3 := p;
  p1.x := 0; p1.y := 0; p1.z := 0;
  res := p2.x*p3.x + p2.y*p3.y + p2.z*p3.z;
  scalar_prod := res;
end;


function cross_prod(p1,p2,p3:p_3d):p_3d;
var
  p : p_3d;
  orig_p1 : p_3d;
begin
  orig_p1 := p1;
  p := subtract_3d(p2,p1);
  p2 := p;
  p := subtract_3d(p3,p1);
  p3 := p;
  p.x := p2.y*p3.z - p2.z*p3.y;
  p.y := p2.z*p3.x - p2.x*p3.z;
  p.z := p2.x*p3.y - p2.y*p3.x;
  cross_prod := add_3d(orig_p1,p);
end;


function angle_3d(p1,p2,p3:p_3d):double;
var
  lp1,lp2,lp3 : p_3d;
  p : p_3d;
  res : double;
  magn_1, magn_2 : double;
  cos_phi : double;
begin
  res := 0;
  lp1 := p1; lp2 := p2; lp3 := p3;
  p := subtract_3d(lp2,lp1);
  lp2 := p;
  p := subtract_3d(lp3,lp1);
  lp3 := p;
  lp1.x := 0; lp1.y := 0; lp1.z := 0;
  magn_1 := dist3d(lp1,lp2);
  magn_2 := dist3d(lp1,lp3);
  if (magn_1 * magn_2 = 0) then
    begin   // emergency exit
      angle_3d := pi;
      exit;
    end;
  cos_phi := scalar_prod(lp1,lp2,lp3) / (magn_1 * magn_2);
  if cos_phi < -1 then cos_phi := -1;
  if cos_phi > 1  then cos_phi := 1;
  res := arccos(cos_phi);
  angle_3d := res;
end;


function angle_2d_XY(p1,p2,p3:p_3d):double;
var   // p1 is the corner
  lp1,lp2,lp3 : p_3d;
  p : p_3d;
  res : double;
  magn_1, magn_2 : double;
  cos_phi : double;
begin
  res := 0;
  lp1 := p1; lp2 := p2; lp3 := p3;
  lp1.z := 0; lp2.z := 0; lp3.z := 0;  // quick and (very) dirty
  p := subtract_3d(lp2,lp1);
  lp2 := p;
  p := subtract_3d(lp3,lp1);
  lp3 := p;
  lp1.x := 0; lp1.y := 0; lp1.z := 0;
  magn_1 := dist3d(lp1,lp2);
  magn_2 := dist3d(lp1,lp3);
  if (magn_1 * magn_2 = 0) then
    begin   // emergency exit
      angle_2d_XY := pi;
      exit;
    end;
  cos_phi := scalar_prod(lp1,lp2,lp3) / (magn_1 * magn_2);
  if cos_phi < -1 then cos_phi := -1;
  if cos_phi > 1  then cos_phi := 1;
  res := arccos(cos_phi);
  angle_2d_XY := res;
end;


function angle_2d_XZ(p1,p2,p3:p_3d):double;
var   // p1 is the corner
  lp1,lp2,lp3 : p_3d;
  p : p_3d;
  res : double;
  magn_1, magn_2 : double;
  cos_phi : double;
begin
  res := 0;
  lp1 := p1; lp2 := p2; lp3 := p3;
  lp1.y := 0; lp2.y := 0; lp3.y := 0;  // quick and (very) dirty
  p := subtract_3d(lp2,lp1);
  lp2 := p;
  p := subtract_3d(lp3,lp1);
  lp3 := p;
  lp1.x := 0; lp1.y := 0; lp1.z := 0;
  magn_1 := dist3d(lp1,lp2);
  magn_2 := dist3d(lp1,lp3);
  if (magn_1 * magn_2 = 0) then
    begin   // emergency exit
      angle_2d_XZ := pi;
      exit;
    end;
  cos_phi := scalar_prod(lp1,lp2,lp3) / (magn_1 * magn_2);
  if cos_phi < -1 then cos_phi := -1;
  if cos_phi > 1  then cos_phi := 1;
  res := arccos(cos_phi);
  angle_2d_XZ := res;
end;


function angle_2d_YZ(p1,p2,p3:p_3d):double;
var   // p1 is the corner
  lp1,lp2,lp3 : p_3d;
  p : p_3d;
  res : double;
  magn_1, magn_2 : double;
  cos_phi : double;
begin
  res := 0;
  lp1 := p1; lp2 := p2; lp3 := p3;
  lp1.x := 0; lp2.x := 0; lp3.x := 0;  // quick and (very) dirty
  p := subtract_3d(lp2,lp1);
  lp2 := p;
  p := subtract_3d(lp3,lp1);
  lp3 := p;
  lp1.x := 0; lp1.y := 0; lp1.z := 0;
  magn_1 := dist3d(lp1,lp2);
  magn_2 := dist3d(lp1,lp3);
  if (magn_1 * magn_2 = 0) then
    begin   // emergency exit
      angle_2d_YZ := pi;
      exit;
    end;
  cos_phi := scalar_prod(lp1,lp2,lp3) / (magn_1 * magn_2);
  if cos_phi < -1 then cos_phi := -1;
  if cos_phi > 1  then cos_phi := 1;
  res := arccos(cos_phi);
  angle_2d_YZ := res;
end;


function ctorsion(p1,p2,p3,p4:p_3d):double;
// calculates "pseudo-torsion" defined by atoms 3 and 4, being both
// attached to atom 2, with respect to axis of atoms 1 and 2
var
  lp1,lp2,lp3,lp4 : p_3d;
  //d1 : p_3d;
  c1,c2 : p_3d;
  res : double;
  c1xc2, c2xc1 : p_3d;
  dist1,dist2 : double;
  sign : double;
begin
  // copy everything into local variables
  lp1 := p1; lp2 := p2; lp3 := p3; lp4 := p4;
  // get the cross product vectors
  c1 := cross_prod(lp2,lp1,lp3);
  c2 := cross_prod(lp2,lp1,lp4);
  res := angle_3d(p2,c1,c2);
  //now check if it is clockwise or anticlockwise:
  //first, make the cross products of the two cross products c1 and c2 (both ways)
  c1xc2 := cross_prod(lp2,c1,c2);
  c2xc1 := cross_prod(lp2,c2,c1);
  //next, get the distances from these points to our refernce point lp1
  dist1 := dist3d(lp1,c1xc2);
  dist2 := dist3d(lp1,c2xc1);
  if (dist1 <= dist2) then sign := 1 else sign := -1;
  ctorsion := sign*res;
end;

//====================== end of geometry functions ==========================

procedure show_usage;
var
  appname : string;
  outputstr, outputext : string;
begin
  if (progmode = pmMol2PS) then 
    begin
      appname := 'mol2ps';
      outputstr := 'Postscript';
      outputext := 'ps';
    end else
    begin
      appname := 'mol2ps';
      appname := 'mol2svg';
      outputstr := 'SVG';
      outputext := 'svg';
    end;
  writeln;
  writeln(appname,' version ',version,'    N. Haider 2014');
  writeln('Usage: ',appname,' [options] <inputfile>');
  writeln(' where <inputfile> is the file containing the molecular structure');
  writeln(' (supported formats: MDL *.mol or *.sdf, Alchemy *.mol, Sybyl *.mol2)');
  writeln(' if <inputfile> is "-" (without quotes), the program reads from standard input');
  writeln;
  writeln('valid options are:');
  writeln('  -R (reaction mode, for MDL rxn and rdf files)');
  writeln('  --font=<Helvetica|Times>, default: Helvetica');
  writeln('  --fontsize=<any number in points>, default: 14');
  writeln('  --fontsizesmall=<any number in points>, default: 9 (for subscripts)');
  writeln('  --linewidth=<n.n>, default: 1.0 (linewidth in points; use 1 decimal)');
  writeln('  --rotate=<auto|auto3Donly|n,n,n>, default: auto (n,n,n specifies the');
  writeln('    angles to rotate the molecule around the X, Y, and Z axis (in degrees)');
  writeln('  --autoscale=<on|off>, default: on (scales the molecule to fit the natural');
  writeln('    C-C bond length)');
  writeln('  --striphydrogen=<on|off>, default: on (strips all explicit H atoms)');
  writeln('  --hydrogenonhetero=<on|off>, default: on (adds H to all hetero atoms)');
  writeln('  --hydrogenonmethyl=<on|off>, default: on (adds H to all methyl C atoms)');
  writeln('  --hydrogenonstereo=<on|off>, default: on (shows H if bond is "up" or "down")');
  writeln('  --showmolname=<on|off>, default: off (prints name above the structure)');
  writeln('  --atomnumbers=<on|off>, default: off (prints atom numbers)');
  writeln('  --bondnumbers=<on|off>, default: off (prints bond numbers)');
  writeln('  --sgroups=<on|off>, default: on (uses Sgroup abbreviations if present)');
  writeln('  --showmaps=<on|off>, default: off (prints atom-atom mapping numbers)');
  writeln('  --color=</path/to/color.conf>, default: no colors for atom labels');
  writeln('  --bgcolor=<white|gray|n,n,n> where n,n,n are the RGB values (0-255)');
  writeln('  --scaling=<n.n>, default: 1.0 (any scaling factor from 0.1 to 10.0)');
  writeln('  --output=<ps|eps|svg>, default depends on prog name (mol2ps, mol2eps, mol2svg)');
  writeln;
  writeln(outputstr,' output will be written to standard output. To write it to a');
  writeln('file, enter something like the following:');
  writeln(appname,' [options] mymolecule.mol > mymolecule.',outputext);
  writeln;
end;


procedure parse_args;
var
  p : integer;
  parstr : string;
  tmpstr : string;
  valstr : string;
  xvalstr, yvalstr, zvalstr : string;
  tmpint, code : integer;
  tmpdbl : double;
  int1, int2, int3 : integer;
begin
  tmpstr := '';
  for p := 1 to paramcount do
    begin
      parstr := paramstr(p);
      if (p < paramcount) then
        begin
          if (pos('-R',parstr)>0) then rxn_mode := true;
          if (pos('--',parstr)=1) then
            begin
              tmpstr := paramstr(p);
              left_trim(tmpstr);
              tmpstr := lowercase(tmpstr);
              if (pos('--font=',tmpstr)=0) and
                 (pos('--fontsize=',tmpstr)=0) and
                 (pos('--fontsizesmall=',tmpstr)=0) and
                 (pos('--linewidth=',tmpstr)=0) and
                 (pos('--rotate=',tmpstr)=0) and
                 (pos('--autoscale=',tmpstr)=0) and
                 (pos('--striphydrogen=',tmpstr)=0) and
                 (pos('--hydrogenonhetero=',tmpstr)=0) and
                 (pos('--hydrogenonmethyl=',tmpstr)=0) and
                 (pos('--atomnumbers=',tmpstr)=0) and
                 (pos('--bondnumbers=',tmpstr)=0) and
                 (pos('--showmolname=',tmpstr)=0) and
                 (pos('--sgroups=',tmpstr)=0) and
                 (pos('--showmaps=',tmpstr)=0) and
                 (pos('--scaling=',tmpstr)=0) and
                 (pos('--output=',tmpstr)=0) and
                 (pos('--bgcolor=',tmpstr)=0) and
                 (pos('--color=',tmpstr)=0) then
                 begin
                   show_usage;
                   halt(1);
                 end;
              if pos('--font=',tmpstr)>0 then
                begin
                  if pos('=helvetica',tmpstr)>0 then
                    begin
                      fontname     := 'Helvetica';
                    end else
                    begin
                      if pos('=times',tmpstr)>0 then
                        begin
                          if progmode = pmMol2PS then fontname     := 'Times Roman';
                          if progmode = pmMol2SVG then fontname     := 'Times';
                        end else fontname := defaultfontname;
                    end;
                end;
              if pos('--fontsize=',tmpstr)>0 then
                begin
                  valstr := tmpstr;
                  delete(valstr,1,pos('=',tmpstr));
                  trimleft(valstr); trimright(valstr);
                  val(valstr,tmpint,code);
                  if (code = 0) then
                    begin
                      if (tmpint >= 6) and (tmpint <= 64) then
                        begin
                          fontsize1 := tmpint;
                        end;
                    end;
                end;
              if pos('--fontsizesmall=',tmpstr)>0 then
                begin
                  valstr := tmpstr;
                  delete(valstr,1,pos('=',tmpstr));
                  trimleft(valstr); trimright(valstr);
                  val(valstr,tmpint,code);
                  if (code = 0) then
                    begin
                      if (tmpint >= 6) and (tmpint <= 64) then
                        begin
                          fontsize2 := tmpint;
                        end;
                    end;
                end;
              if pos('--linewidth=',tmpstr)>0 then
                begin
                  valstr := tmpstr;
                  delete(valstr,1,pos('=',tmpstr));
                  trimleft(valstr); trimright(valstr);
                  val(valstr,tmpdbl,code);
                  if (code = 0) then
                    begin
                      if (tmpdbl >= 0.1) and (tmpdbl <= 10) then
                        begin
                          linewidth := tmpdbl;
                        end;
                    end;
                end;
              if pos('--rotate=',tmpstr)>0 then
                begin
                  opt_autorotate := false;
                  opt_autorotate3Donly := false;
                  valstr := tmpstr;
                  delete(valstr,1,pos('=',tmpstr));
                  trimleft(valstr); trimright(valstr);
                  if (valstr = 'auto') or (valstr = 'auto3Donly') then
                    begin
                      if (valstr = 'auto') then opt_autorotate := true;
                      if (valstr = 'auto3Donly') then opt_autorotate3Donly := true;
                    end else
                    begin
                      opt_autorotate := false;
                      opt_autorotate3Donly := false;
                      xvalstr := ''; yvalstr := ''; zvalstr := '';
                      while (length(valstr)>0) and (valstr[1] <> ',') do
                        begin
                          xvalstr := xvalstr + valstr[1];
                          delete(valstr,1,1);
                        end;
                      while (length(valstr)>0) and (valstr[1] = ',') do delete(valstr,1,1);
                      while (length(valstr)>0) and (valstr[1] <> ',') do
                        begin
                          yvalstr := yvalstr + valstr[1];
                          delete(valstr,1,1);
                        end;
                      while (length(valstr)>0) and (valstr[1] = ',') do delete(valstr,1,1);
                      while (length(valstr)>0) and (valstr[1] <> ',') do
                        begin
                          zvalstr := zvalstr + valstr[1];
                          delete(valstr,1,1);
                        end;
                      val(xvalstr,tmpdbl,code);
                      if (code = 0) then
                        begin
                          xrot := degtorad(tmpdbl);
                        end else xrot := 0;
                      val(yvalstr,tmpdbl,code);
                      if (code = 0) then
                        begin
                          yrot := degtorad(tmpdbl);
                        end else yrot := 0;
                      val(zvalstr,tmpdbl,code);
                      if (code = 0) then
                        begin
                          zrot := degtorad(tmpdbl);
                        end else zrot := 0;
                    end;
                end;             // rotate=
              if pos('--autoscale=',tmpstr)>0 then
                begin
                  valstr := tmpstr;
                  delete(valstr,1,pos('=',tmpstr));
                  trimleft(valstr); trimright(valstr);
                  if (valstr = 'on')  then opt_autoscale := true;
                  if (valstr = 'off') then opt_autoscale := false;                      
                end;
              if pos('--striphydrogen=',tmpstr)>0 then
                begin
                  valstr := tmpstr;
                  delete(valstr,1,pos('=',tmpstr));
                  trimleft(valstr); trimright(valstr);
                  if (valstr = 'on') then opt_stripH := true;
                  if (valstr = 'off') then opt_stripH := false;                      
                end;
              if pos('--hydrogenonhetero=',tmpstr)>0 then
                begin
                  valstr := tmpstr;
                  delete(valstr,1,pos('=',tmpstr));
                  trimleft(valstr); trimright(valstr);
                  if (valstr = 'on') then opt_Honhetero := true;
                  if (valstr = 'off') then opt_Honhetero := false;                      
                end;
              if pos('--hydrogenonmethyl=',tmpstr)>0 then
                begin
                  valstr := tmpstr;
                  delete(valstr,1,pos('=',tmpstr));
                  trimleft(valstr); trimright(valstr);
                  if (valstr = 'on') then opt_Honmethyl := true;
                  if (valstr = 'off') then opt_Honmethyl := false;                      
                end;
              if pos('--hydrogenonstereo=',tmpstr)>0 then
                begin
                  valstr := tmpstr;
                  delete(valstr,1,pos('=',tmpstr));
                  trimleft(valstr); trimright(valstr);
                  if (valstr = 'on') then opt_Honstereo := true;
                  if (valstr = 'off') then opt_Honstereo := false;                      
                end;
              if pos('--showmolname=',tmpstr)>0 then
                begin
                  valstr := tmpstr;
                  delete(valstr,1,pos('=',tmpstr));
                  trimleft(valstr); trimright(valstr);
                  if (valstr = 'on') then opt_showmolname := true;
                  if (valstr = 'off') then opt_showmolname := false;                      
                end;
              if pos('--atomnumbers=',tmpstr)>0 then
                begin
                  valstr := tmpstr;
                  delete(valstr,1,pos('=',tmpstr));
                  trimleft(valstr); trimright(valstr);
                  if (valstr = 'on') then opt_atomnum := true;
                  if (valstr = 'off') then opt_atomnum := false;                      
                end;
              if pos('--bondnumbers=',tmpstr)>0 then
                begin
                  valstr := tmpstr;
                  delete(valstr,1,pos('=',tmpstr));
                  trimleft(valstr); trimright(valstr);
                  if (valstr = 'on') then opt_bondnum := true;
                  if (valstr = 'off') then opt_bondnum := false;                      
                end;
              if pos('--color=',tmpstr)>0 then
                begin
                  valstr := tmpstr;
                  delete(valstr,1,pos('=',tmpstr));
                  trimleft(valstr); trimright(valstr);
                  rgbfilename := valstr;
                  opt_color := true;
                end;
              if pos('--sgroups=',tmpstr)>0 then   // v0.2a
                begin
                  valstr := tmpstr;
                  delete(valstr,1,pos('=',tmpstr));
                  trimleft(valstr); trimright(valstr);
                  if (valstr = 'on') then opt_sgroups := true;
                  if (valstr = 'off') then opt_sgroups := false;                      
                end;
              if pos('--showmaps=',tmpstr)>0 then   // v0.3a
                begin
                  valstr := tmpstr;
                  delete(valstr,1,pos('=',tmpstr));
                  trimleft(valstr); trimright(valstr);
                  if (valstr = 'on') then opt_maps := true;
                  if (valstr = 'off') then opt_maps := false;                      
                end;
              if pos('--scaling=',tmpstr)>0 then
                begin
                  valstr := tmpstr;
                  delete(valstr,1,pos('=',tmpstr));
                  trimleft(valstr); trimright(valstr);
                  val(valstr,tmpdbl,code);
                  if (code = 0) then
                    begin
                      if (tmpdbl >= 0.1) and (tmpdbl <= 10) then
                        begin
                          global_scaling := tmpdbl;
                        end;
                    end;
                end;
              if pos('--bgcolor=',tmpstr)>0 then
                begin
                  valstr := tmpstr;
                  delete(valstr,1,pos('=',tmpstr));
                  trimleft(valstr); trimright(valstr);
                  if (valstr = 'white') or (valstr = 'gray') then
                    begin
                      opt_bgcolor := true;
                      if (valstr = 'white') then 
                        begin
                          bgcolor.r := 255;
                          bgcolor.g := 255;
                          bgcolor.b := 255;
                        end;
                      if (valstr = 'gray') then 
                        begin
                          bgcolor.r := 224;
                          bgcolor.g := 224;
                          bgcolor.b := 224;
                        end;
                    end else
                    begin
                      xvalstr := ''; yvalstr := ''; zvalstr := '';
                      while (length(valstr)>0) and (valstr[1] <> ',') do
                        begin
                          xvalstr := xvalstr + valstr[1];
                          delete(valstr,1,1);
                        end;
                      while (length(valstr)>0) and (valstr[1] = ',') do delete(valstr,1,1);
                      while (length(valstr)>0) and (valstr[1] <> ',') do
                        begin
                          yvalstr := yvalstr + valstr[1];
                          delete(valstr,1,1);
                        end;
                      while (length(valstr)>0) and (valstr[1] = ',') do delete(valstr,1,1);
                      while (length(valstr)>0) and (valstr[1] <> ',') do
                        begin
                          zvalstr := zvalstr + valstr[1];
                          delete(valstr,1,1);
                        end;
                      val(xvalstr,int1,code);
                      if (code <> 0) then int1 := -1;
                      val(yvalstr,int2,code);
                      if (code <> 0) then int2 := -1;
                      val(zvalstr,int3,code);
                      if (code <> 0) then int3 := -1;
                      if ((int1 >= 0) and (int1 <= 255) and
                          (int2 >= 0) and (int2 <= 255) and
                          (int3 >= 0) and (int3 <= 255)) then
                        begin
                          opt_bgcolor := true;
                          bgcolor.r := int1;
                          bgcolor.g := int2;
                          bgcolor.b := int3;
                        end;
                    end;
                end;             // bgcolor=
              if pos('--output=',tmpstr)>0 then
                begin
                  if pos('=ps',tmpstr)>0 then
                    begin
                      progmode := pmMol2PS;
                      opt_eps := false;
                    end else
                    begin
                      if pos('=eps',tmpstr)>0 then
                        begin
                          progmode := pmMol2PS;
                          opt_eps := true;
                        end else progmode := pmMol2SVG;
                    end;
                end;
              // some more options still to come...  
            end;
          {$IFDEF debug}
          if (parstr = '-D') then opt_debug := true;  // v0.1f
          {$ENDIF}
        end else
        begin
          if (pos('-',parstr)=1) then
            begin
              if (length(parstr)>1) and (rxn_mode = false) then
                begin
                  show_usage;
                  halt(1);
                end else
                begin
                  opt_stdin := true;
                end;
            end else
              begin
                opt_stdin := false;
                molfilename := parstr;
              end;
        end;
    end;
end;


//============== input-related functions & procedures =====================

function get_filetype(f:string):string;
var
  rline : string;
  auxstr : string;
  i : integer;
  mdl1 : boolean;
  ri : integer;
  sepcount : integer;
begin
  auxstr := 'unknown';
  i := li; mdl1 := false;
  ri := li -1;
  sepcount := 0;
  while (ri < molbufindex) and (sepcount < 1) do
    begin
      inc(ri);
      rline := molbuf^[ri];
      if (pos('$$$$',rline)>0) then inc(sepcount);
      if (i = li) and (copy(rline,7,5)='ATOMS')
                 and (copy(rline,20,5)='BONDS')
                 and (copy(rline,33,7)='CHARGES') then
        begin
          auxstr := 'alchemy';
        end;
      if (i = li+3) // and (copy(rline,31,3)='999')
                 and (copy(rline,35,5)='V2000')      then mdl1 := true;
      if (i = li+1) and (copy(rline,3,6)='-ISIS-')      then mdl1 := true;
      if (i = li+1) and (copy(rline,3,8)='WLViewer')    then mdl1 := true;
      if (i = li+1) and (copy(rline,3,8)='CheckMol')    then mdl1 := true;
      if (i = li+1) and (copy(rline,3,8)='CATALYST') then
        begin
          mdl1 := true;
          auxstr := 'mdl';
        end;
      if (pos('M  END',rline)=1) or mdl1 then
        begin
          auxstr := 'mdl';
        end;
      if pos('@<TRIPOS>MOLECULE',rline)>0 then
        begin
          auxstr := 'sybyl';
        end;
      inc(i);
    end;
  // try to identify non-conformant SD-files
  if (auxstr = 'unknown') and (sepcount > 0) then auxstr := 'mdl';
  get_filetype := auxstr;
end;


procedure zap_molecule;
begin
  try
    if atom <> nil then 
      begin
        freemem(atom,n_atoms*sizeof(atom_rec));
        atom := nil;
      end;
    if bond <> nil then 
      begin
        freemem(bond,n_bonds*sizeof(bond_rec));
        bond := nil;
      end;
    if ring <> nil then 
      begin
        freemem(ring,sizeof(ringlist));
        ring := nil;
      end;  
    if ringprop <> nil then 
      begin
        freemem(ringprop,sizeof(ringprop_type));
        ringprop := nil;
      end;
    if bracket <> nil then  // v0.1f
      begin
        freemem(bracket,sizeof(bracket_type));
        bracket := nil;
      end;
    if sgroup <> nil then  // v0.2a
      begin
        freemem(sgroup,sizeof(sgroup_type));
        sgroup := nil;
      end;
  except
    on e:Einvalidpointer do begin end;
  end;
  n_atoms := 0;
  n_bonds := 0;
  n_rings := 0;
  n_brackets := 0; // v0.1f
  n_sgroups  := 0; // v0.2a
end;


function is_heavyatom(id:integer):boolean;
var
  r  : boolean;
  el : str2;
begin
  r  := true;
  el := atom^[id].element;
  if (el = 'H ') or (el = 'DU') or (el = 'LP') then r := false;
  if (el = 'H ') and (atom^[id].nucleon_number > 1) then r:= true;
  is_heavyatom := r;
end;


function is_metal(id:integer):boolean;
var
  r  : boolean;
  el : str2;
begin
  r  := false;
  el := atom^[id].element;
  if (el = 'LI') or (el = 'NA') or (el = 'K ') or (el = 'RB') or (el = 'CS') or
     (el = 'BE') or (el = 'MG') or (el = 'CA') or (el = 'SR') or (el = 'BA') or
     (el = 'TI') or (el = 'ZR') or (el = 'CR') or (el = 'MO') or (el = 'MN') or
     (el = 'FE') or (el = 'CO') or (el = 'NI') or (el = 'PD') or (el = 'PT') or
     (el = 'SN') or (el = 'CU') or (el = 'AG') or (el = 'AU') or (el = 'ZN') or 
     (el = 'CD') or (el = 'HG') or (el = 'AL') or (el = 'SN') or (el = 'PB') or 
     (el = 'SB') or (el = 'BI')                                   // etc. etc.
    then r := true;
  is_metal := r;
end;


function get_nvalences(a_el:str2):integer;  
// preliminary version; should be extended to element/atomtype
var
  res : integer;
begin
  res := 1;
  if a_el = 'H ' then res := 1;
  if a_el = 'D ' then res := 1;  
  if a_el = 'C ' then res := 4;
  if a_el = 'N ' then res := 3;
  if a_el = 'O ' then res := 2;
  if a_el = 'S ' then res := 2;
  if a_el = 'SE' then res := 2;
  if a_el = 'TE' then res := 2;
  if a_el = 'P ' then res := 3;
  if a_el = 'F ' then res := 1;
  if a_el = 'CL' then res := 1;
  if a_el = 'BR' then res := 1;
  if a_el = 'I ' then res := 1;
  if a_el = 'B ' then res := 3;
  if a_el = 'LI' then res := 1;
  if a_el = 'NA' then res := 1;
  if a_el = 'K ' then res := 1;
  if a_el = 'CA' then res := 2;
  if a_el = 'SR' then res := 2;
  if a_el = 'MG' then res := 2;
  if a_el = 'FE' then res := 3;
  if a_el = 'MN' then res := 2;
  if a_el = 'HG' then res := 2;
  if a_el = 'SI' then res := 4;
  if a_el = 'SN' then res := 4;
  if a_el = 'ZN' then res := 2;
  if a_el = 'CU' then res := 2;
  if a_el = 'A ' then res := 4;
  if a_el = 'Q ' then res := 4;
  get_nvalences := res;
end;


function convert_type(oldtype : str4):str3;
var
  i : integer;
  newtype : str3;
begin
  newtype := copy(oldtype,1,3);
  for i := 1 to 3 do newtype[i] := upcase(newtype[i]);
  if newtype[1] = '~' then newtype := 'VAL';
  If newtype[1] = '*' then newtype := 'STR';
  convert_type := newtype;
end;


function convert_sybtype(oldtype : str5):str3;
var
  newtype : str3;
begin
//  NewType := Copy(OldType,1,3);
//  For i := 1 To 3 Do NewType[i] := UpCase(NewType[i]);
//  If NewType[1] = '~' Then NewType := 'VAL';
//  If NewType[1] = '*' Then NewType := 'STR';
  newtype := 'DU ';
  if oldtype = 'H    ' then newtype := 'H  ';
  if oldtype = 'C.ar ' then newtype := 'CAR';
  if oldtype = 'C.2  ' then newtype := 'C2 ';
  if oldtype = 'C.3  ' then newtype := 'C3 ';
  if oldtype = 'C.1  ' then newtype := 'C1 ';
  if oldtype = 'O.2  ' then newtype := 'O2 ';
  if oldtype = 'O.3  ' then newtype := 'O3 ';
  if oldtype = 'O.co2' then newtype := 'O2 ';
  if oldtype = 'O.spc' then newtype := 'O3 ';
  if oldtype = 'O.t3p' then newtype := 'O3 ';
  if oldtype = 'N.1  ' then newtype := 'N1 ';
  if oldtype = 'N.2  ' then newtype := 'N2 ';
  if oldtype = 'N.3  ' then newtype := 'N3 ';
  if oldtype = 'N.pl3' then newtype := 'NPL';
  if oldtype = 'N.4  ' then newtype := 'N3+';
  if oldtype = 'N.am ' then newtype := 'NAM';
  if oldtype = 'N.ar ' then newtype := 'NAR';
  if oldtype = 'F    ' then newtype := 'F  ';
  if oldtype = 'Cl   ' then newtype := 'CL ';
  if oldtype = 'Br   ' then newtype := 'BR ';
  if oldtype = 'I    ' then newtype := 'I  ';
  if oldtype = 'Al   ' then newtype := 'AL ';
  if oldtype = 'ANY  ' then newtype := 'A  ';
  if oldtype = 'Ca   ' then newtype := 'CA ';
  if oldtype = 'Du   ' then newtype := 'DU ';
  if oldtype = 'Du.C ' then newtype := 'DU ';
  if oldtype = 'H.spc' then newtype := 'H  ';
  if oldtype = 'H.t3p' then newtype := 'H  ';
  if oldtype = 'HAL  ' then newtype := 'Cl ';
  if oldtype = 'HET  ' then newtype := 'Q  ';
  if oldtype = 'HEV  ' then newtype := 'DU ';
  if oldtype = 'K    ' then newtype := 'K  ';
  if oldtype = 'Li   ' then newtype := 'LI ';
  if oldtype = 'LP   ' then newtype := 'LP ';
  if oldtype = 'Na   ' then newtype := 'NA ';
  if oldtype = 'P.3  ' then newtype := 'P3 ';
  if oldtype = 'S.2  ' then newtype := 'S2 ';
  if oldtype = 'S.3  ' then newtype := 'S3 ';
  if oldtype = 'S.o  ' then newtype := 'SO ';
  if oldtype = 'S.o2 ' then newtype := 'SO2';
  if oldtype = 'Si   ' then newtype := 'SI ';
  if oldtype = 'P.4  ' then newtype := 'P4 ';
  convert_sybtype := newtype;
end;


function convert_MDLtype(oldtype : str3):str3;
var
  newtype : str3;
begin
//  NewType := Copy(OldType,1,3);
//  For i := 1 To 3 Do NewType[i] := UpCase(NewType[i]);
//  If NewType[1] = '~' Then NewType := 'VAL';
//  If NewType[1] = '*' Then NewType := 'STR';
  newtype := 'DU ';
  if oldtype = 'H  ' then newtype := 'H  ';
  if oldtype = 'C  ' then newtype := 'C3 ';
  if oldtype = 'O  ' then newtype := 'O2 ';
  if oldtype = 'N  ' then newtype := 'N3 ';
  if oldtype = 'F  ' then newtype := 'F  ';
  if oldtype = 'Cl ' then newtype := 'CL ';
  if oldtype = 'Br ' then newtype := 'BR ';
  if oldtype = 'I  ' then newtype := 'I  ';
  if oldtype = 'Al ' then newtype := 'AL ';
  if oldtype = 'ANY' then newtype := 'A  ';
  if oldtype = 'Ca ' then newtype := 'CA ';
  if oldtype = 'Du ' then newtype := 'DU ';
  if oldtype = 'K  ' then newtype := 'K  ';
  if oldtype = 'Li ' then newtype := 'LI ';
  if oldtype = 'LP ' then newtype := 'LP ';
  if oldtype = 'Na ' then newtype := 'NA ';
  if oldtype = 'P  ' then newtype := 'P3 ';
  if oldtype = 'S  ' then newtype := 'S3 ';
  if oldtype = 'Si ' then newtype := 'SI ';
  if oldtype = 'P  ' then newtype := 'P4 ';
  if oldtype = 'A  ' then newtype := 'A  ';
  if oldtype = 'Q  ' then newtype := 'Q  ';
  convert_MDLtype := NewType;
end;


function get_element(oldtype:str4):str2;
var
  elemstr : string;
begin
  if oldtype = 'H   ' then elemstr := 'H ';
  if oldtype = 'CAR ' then elemstr := 'C ';
  if oldtype = 'C2  ' then elemstr := 'C ';
  if oldtype = 'C3  ' then elemstr := 'C ';
  if oldtype = 'C1  ' then elemstr := 'C ';
  if oldtype = 'O2  ' then elemstr := 'O ';
  if oldtype = 'O3  ' then elemstr := 'O ';
  if oldtype = 'O2  ' then elemstr := 'O ';
  if oldtype = 'O3  ' then elemstr := 'O ';
  if oldtype = 'O3  ' then elemstr := 'O ';
  if oldtype = 'N1  ' then elemstr := 'N ';
  if oldtype = 'N2  ' then elemstr := 'N ';
  if oldtype = 'N3  ' then elemstr := 'N ';
  if oldtype = 'NPL ' then elemstr := 'N ';
  if oldtype = 'N3+ ' then elemstr := 'N ';
  if oldtype = 'NAM ' then elemstr := 'N ';
  if oldtype = 'NAR ' then elemstr := 'N ';
  if oldtype = 'F   ' then elemstr := 'F ';
  if oldtype = 'CL  ' then elemstr := 'CL';
  if oldtype = 'BR  ' then elemstr := 'BR';
  if oldtype = 'I   ' then elemstr := 'I ';
  if oldtype = 'AL  ' then elemstr := 'AL';
  if oldtype = 'DU  ' then elemstr := 'DU';
  if oldtype = 'CA  ' then elemstr := 'CA';
  if oldtype = 'DU  ' then elemstr := 'DU';
  if oldtype = 'Cl  ' then elemstr := 'CL';
  if oldtype = 'K   ' then elemstr := 'K ';
  if oldtype = 'LI  ' then elemstr := 'LI';
  if oldtype = 'LP  ' then elemstr := 'LP';
  if oldtype = 'NA  ' then elemstr := 'NA';
  if oldtype = 'P3  ' then elemstr := 'P ';
  if oldtype = 'S2  ' then elemstr := 'S ';
  if oldtype = 'S3  ' then elemstr := 'S ';
  if oldtype = 'SO  ' then elemstr := 'S ';
  if oldtype = 'SO2 ' then elemstr := 'S ';
  if oldtype = 'SI  ' then elemstr := 'SI';
  if oldtype = 'P4  ' then elemstr := 'P ';
  if oldtype = 'A   ' then elemstr := 'A ';
  if oldtype = 'Q   ' then elemstr := 'Q ';
  get_element := elemstr;
end;


function get_sybelement(oldtype:str5):str2;
var
  i : integer;
  elemstr : str2;
begin
  if pos('.',oldtype)<2 then
    begin
      elemstr := copy(oldtype,1,2);
    end else
    begin
      elemstr := copy(oldtype,1,pos('.',oldtype)-1);
      if length(elemstr)<2 then elemstr := elemstr+' ';
    end;
  for i := 1 to 2 do elemstr[i] := upcase(elemstr[i]);
  get_sybelement := elemstr;
end;


function get_MDLelement(oldtype:str3):str2;
var
  i : integer;
  elemstr : str2;
begin
  elemstr := copy(oldtype,1,2);
  for i := 1 to 2 do elemstr[i] := upcase(elemstr[i]);
  if elemstr[1] = '~' then elemstr := '??';
  if elemstr[1] = '*' then elemstr := '??';
  {$IFDEF clean_marvin_r}
  if elemstr = 'R#' then elemstr := 'R ';
  {$ENDIF}
  get_MDLelement := elemstr;
end;


procedure read_molfile(mfilename:string);  // reads ALCHEMY mol files
var
  n, code : integer;
  rline, tmpstr : string;
  xstr, ystr, zstr, chgstr : string;
  xval, yval, zval, chgval : single;
  a1str, a2str, elemstr : string;
  a1val, a2val : integer;
  ri : integer;
begin
  if n_atoms > 0 then zap_molecule;
  ri := li;
  rline := molbuf^[ri];
  tmpstr := copy(rline,1,5);
  val(tmpstr,n_atoms,code);
  tmpstr := copy(rline,14,5);
  val(tmpstr,n_bonds,code);
  molname := copy(rline,42,length(rline)-42);
  try
    getmem(atom,n_atoms*sizeof(atom_rec));
    getmem(bond,n_bonds*sizeof(bond_rec));
    getmem(ring,sizeof(ringlist));
    getmem(ringprop,sizeof(ringprop_type));
    getmem(bracket,sizeof(bracket_type));  // v0.1f
    getmem(sgroup,sizeof(sgroup_type));  // v0.2a
  except
    on e:Eoutofmemory do
      begin
        writeln('Not enough memory');
        halt(4);
      end;
  end;
  n_heavyatoms := 0;
  n_heavybonds := 0;
  for n := 1 to n_atoms do
    begin
      with atom^[n] do
        begin
          x := 0; y := 0; z := 0;
          formal_charge  := 0;
          real_charge    := 0;
          Hexp           := 0;
          Htot           := 0;
          neighbor_count := 0;
          ring_count     := 0;
          arom           := FALSE;
          stereo_care    := FALSE;
          map_id         := 0;
        end;
      inc(ri);
      rline := molbuf^[ri];
      atomtype := copy(rline,7,4);
      atomtype := upcase(atomtype);
      elemstr  := get_element(atomtype);
      newatomtype := convert_type(atomtype);
      xstr := copy(rline,14,7);
      ystr := copy(rline,23,7);
      zstr := copy(rline,32,7);
      chgstr := copy(rline,43,7);
      val(xstr,xval,code);
      val(ystr,yval,code);
      val(zstr,zval,code);
      val(chgstr,chgval,code);
      with atom^[n] do
        begin
          element := elemstr;
          atype := newatomtype;
          x := xval; y := yval; z := zval; real_charge := chgval;
          x_orig := xval; y_orig := yval; z_orig := zval;
        end;
      if is_heavyatom(n) then inc(n_heavyatoms);
    end;
  for n := 1 to n_bonds do
    begin
      inc(ri);
      rline := molbuf^[ri];
      a1str := copy(rline,9,3);
      a2str := copy(rline,15,3);
      val(a1str,a1val,code);
      if code <> 0 then beep;
      val(a2str,a2val,code);
      if code <> 0 then beep;
      with bond^[n] do
        begin
          a1 := a1val; a2 := a2val; btype := rline[20];
          ring_count := 0; arom := false; topo := btopo_any; stereo := bstereo_any;
          bsubtype := 'N';
          a_handle := 0;
        end;
      if is_heavyatom(a1val) and is_heavyatom(a2val) then inc(n_heavybonds);
    end;
  fillchar(ring^,sizeof(ringlist),0);
  for n := 1 to max_rings do
    begin
      ringprop^[n].size     := 0;
      ringprop^[n].arom     := false;
      ringprop^[n].envelope := false;
    end;
  li := ri + 1;
end;


procedure read_mol2file(mfilename:string);  // reads SYBYL mol2 files
var
  n, code : integer;
  sybatomtype : string[5];
  tmpstr, rline : string;
  xstr, ystr, zstr, chgstr : string;
  xval, yval, zval, chgval : single;
  a1str, a2str, elemstr : string;
  a1val, a2val : integer;
  ri : integer;
begin
  if n_atoms > 0 then zap_molecule;
  rline := '';
  ri := li -1;
  while (ri < molbufindex) and (pos('@<TRIPOS>MOLECULE',rline)=0) do
    begin
      inc(ri);
      rline := molbuf^[ri];
    end;
  if ri < molbufindex then
    begin
      inc(ri);
      molname := molbuf^[ri];
    end;
  if ri < molbufindex then
    begin
      inc(ri);
      rline := molbuf^[ri];
    end;
  tmpstr := copy(rline,1,5);
  val(tmpstr,n_atoms,code);
  tmpstr := copy(rline,7,5);
  val(tmpstr,n_bonds,code);
  try
    getmem(atom,n_atoms*sizeof(atom_rec));
    getmem(bond,n_bonds*sizeof(bond_rec));
    getmem(ring,sizeof(ringlist));
    getmem(ringprop,sizeof(ringprop_type));
    getmem(bracket,sizeof(bracket_type));  // v0.1f
    getmem(sgroup,sizeof(sgroup_type));  // v0.2a
  except
    on e:Eoutofmemory do
      begin
        writeln('Not enough memory');
        halt(4);
      end;
  end;
  n_heavyatoms := 0;
  n_heavybonds := 0;
  while ((ri < molbufindex) and (pos('@<TRIPOS>ATOM',rline)=0)) do
    begin
      inc(ri);
      rline := molbuf^[ri];
    end;
  for n := 1 to n_atoms do
  begin
    with atom^[n] do
      begin
        x := 0; y := 0; z := 0;
        formal_charge  := 0;
        real_charge    := 0;
        Hexp           := 0;
        Htot           := 0;
        neighbor_count := 0;
        ring_count     := 0;
        arom           := FALSE;
        stereo_care    := false;
        map_id         := 0;
      end;
    if (ri < molbufindex) then
      begin
        inc(ri);
        rline := molbuf^[ri];
      end;
    sybatomtype := copy(rline,48,5);
    elemstr     := get_sybelement(sybatomtype);
    newatomtype := convert_sybtype(sybatomtype);
    xstr := copy(rline,18,9);
    ystr := copy(rline,28,9);
    zstr := copy(rline,38,9);
    chgstr := copy(rline,70,9);
    val(xstr,xval,code);
    val(ystr,yval,code);
    val(zstr,zval,code);
    val(chgstr,chgval,code);
    with atom^[n] do
      begin
        element := elemstr;
        atype := newatomtype;
        x := xval; y := yval; z := zval; real_charge := chgval;
        x_orig := xval; y_orig := yval; z_orig := zval;        
      end;
    if is_heavyatom(n) then inc(n_heavyatoms);
  end;
  while ((ri < molbufindex) and (pos('@<TRIPOS>BOND',rline)=0)) do
    begin
      inc(ri);
      rline := molbuf^[ri];
    end;
  for n := 1 to n_bonds do
  begin
    if (ri < molbufindex) then
      begin
        inc(ri);
        rline := molbuf^[ri];
      end;
    a1str := copy(rline,9,3);
    a2str := copy(rline,14,3);
    val(a1str,a1val,code);
    if code <> 0 then writeln(rline, #7);
    val(a2str,a2val,code);
    if code <> 0 then writeln(rline,#7);
    with bond^[n] do
      begin
        a1 := a1val; a2 := a2val;
        if rline[18] = '1' then btype := 'S';
        if rline[18] = '2' then btype := 'D';
        if rline[18] = '3' then btype := 'T';
        if rline[18] = 'a' then btype := 'A';
        ring_count := 0; arom := false; topo := btopo_any; stereo := bstereo_any;
        bsubtype := 'N';
        a_handle := 0;
      end;
    if is_heavyatom(a1val) and is_heavyatom(a2val) then inc(n_heavybonds);
  end;
  fillchar(ring^,sizeof(ringlist),0);
  for n := 1 to max_rings do
    begin
      ringprop^[n].size     := 0;
      ringprop^[n].arom     := false;
      ringprop^[n].envelope := false;
    end;
  li := ri + 1;
end;


function get_bracket_index(id:integer):integer;
var
  i, r : integer;
begin
  r := 0;
  if (n_brackets > 0) then
    begin
      for i := 1 to n_brackets do
        begin
          if bracket^[i].id = id then r := i;
        end;
    end;
  get_bracket_index := r;
end;


function get_sgroup_index(id:integer):integer;
var
  i, r : integer;
begin
  r := 0;
  if (n_sgroups > 0) then
    begin
      for i := 1 to n_sgroups do
        begin
          if sgroup^[i].id = id then r := i;
        end;
    end;
  get_sgroup_index := r;
end;

procedure read_alias(astring,line2:string);    // v0.2b
var
  a_id : integer;
  a_alias : string;
  a_j : integer;
  // typical example: 
  // A   39
  // Atto647N
  // always 2 lines, the first one with "A   nnn" where nnn is the atom number,
  // the second line contains the alias;
  // the usual markups apply: \S = superscript, \s = subscript, \n = normal
begin
  // MDL specs:
  // A   nnn              // nnn = atom number
  // xxx                  // xxx = the alias text
  // mol2ps extension:
  // A   nnnjjj           // nnn = atom number  jjj = justification ( 0 = left, 1 = right, 2 = center
  // xxx                  // xxx = the alias text
  if (pos('A  ',astring)>0) then
    begin
      delete(astring,1,3);
      left_trim(astring);
      a_id := left_int(astring);  // atom number (MDL standard
      a_j  := left_int(astring);  // justification (mol2ps extension)
      {$IFDEF debug}
      if (a_id = 0) then debugoutput('strange... label for atom 0');
      {$ENDIF}
      if (length(line2) <= 80) then a_alias := line2 else a_alias := copy(line2,1,80);
      if (a_id > 0) and (a_id <= n_atoms) then
        begin
          {$IFDEF debug}
          debugoutput('adding alias '+a_alias+' to atom '+inttostr(a_id));
          {$ENDIF}
          atom^[a_id].alias := a_alias;
          atom^[a_id].a_just := 0;  // default
          if (a_j = 1) or (a_j = 2) then atom^[a_id].a_just := a_j;
        end;
    end; 
end;



procedure read_charges(chgstring:string);
var
  a_id, a_chg : integer;
  n_chrg : integer;
  // typical example: a molecule with 2 cations + 1 anion
  // M  CHG  3   8   1  10   1  11  -1
begin
  if (pos('M  CHG',chgstring)>0) then
    begin
      delete(chgstring,1,6);
      left_trim(chgstring);
      n_chrg := left_int(chgstring);  // this assignment must be kept also in non-debug mode!
      {$IFDEF debug}
      if (n_chrg = 0) then debugoutput('strange... M  CHG present, but no charges found');
      {$ENDIF}
      while (length(chgstring) > 0) do
        begin
          a_id  := left_int(chgstring);
          a_chg := left_int(chgstring);
          if (a_id <> 0) and (a_chg <> 0) then atom^[a_id].formal_charge := a_chg;
        end;
    end;
end;


procedure read_isotopes(isotopestring:string);
var
  a_id, a_nucleon_number : integer;
  n_isotopes : integer;
  // typical example: a molecule with 3 isotopes
  // M  ISO  3   8   15  10   13  11  17
begin
  if (pos('M  ISO',isotopestring) > 0) then
    begin
      delete(isotopestring,1,6);
      left_trim(isotopestring);
      n_isotopes := left_int(isotopestring);  // this assignment must be kept also in non-debug mode!
      {$IFDEF debug}
      if (n_isotopes = 0) then debugoutput('strange... M  ISO with nucleon_numer = 0');
      {$ENDIF}
      while (length(isotopestring) > 0) do
        begin
          a_id  := left_int(isotopestring);
          a_nucleon_number := left_int(isotopestring);
          if (a_id <> 0) and (a_nucleon_number > 0) then 
            begin
              atom^[a_id].nucleon_number := a_nucleon_number;
              if (atom^[a_id].element = 'H ') and (a_nucleon_number > 1) then
                begin
                  //keep_DT := false;
                  //if opt_iso then
                    begin
                      atom^[a_id].heavy := true;
                      inc(n_heavyatoms);
                    end;
                  atom^[a_id].atype := 'DU ';
                end;
            end;
        end;
    end;
end;


procedure read_radicals(radstring:string);
var
  a_id, a_rad : integer;
  n_rads : integer;
  // typical example: a molecule with a radical
  // M  RAD  1   8   2
begin
  if (pos('M  RAD',radstring) > 0) then
    begin
      delete(radstring,1,6);
      left_trim(radstring);
      n_rads := left_int(radstring);  // this assignment must be kept also in non-debug mode!
      {$IFDEF debug}
      if (n_rads = 0) then debugoutput('strange... M  RAD present, but no radicals found');
      {$ENDIF}
      while (length(radstring) > 0) do
        begin
          a_id  := left_int(radstring);
          a_rad := left_int(radstring);
          if (a_id <> 0) and (a_rad <> 0) then atom^[a_id].radical_type := a_rad;
        end;
    end;
end;


procedure read_brackets(sgroupstring:string);
var
  br_id : integer;
  br_index : integer;
  n_tmp : integer;
  xtmp, ytmp : single;
  k : integer;
begin
  if (pos('M  SDI',sgroupstring) > 0) then
    begin
      delete(sgroupstring,1,6);
      left_trim(sgroupstring);
      br_id := left_int(sgroupstring);  // this assignment must be kept also in non-debug mode!
      n_tmp := left_int(sgroupstring);  // this assignment must be kept also in non-debug mode!
      {$IFDEF debug}
      {$ENDIF}
      br_index := get_bracket_index(br_id);
      if (br_index > 0) and (br_index <= n_brackets) then
        begin
          k := 1;
          if (bracket^[br_index].x1 = 0) and (bracket^[br_index].y1 = 0) and
             (bracket^[br_index].x2 = 0) and (bracket^[br_index].y2 = 0) then k := 1;
          if (bracket^[br_index].x1 <> 0) or (bracket^[br_index].y1 <> 0) or
             (bracket^[br_index].x2 <> 0) or (bracket^[br_index].y2 <> 0) then k := 3;
          while (length(sgroupstring) > 0) do
            begin
              xtmp  := left_float(sgroupstring);
              ytmp  := left_float(sgroupstring);
              with bracket^[br_index] do
                begin
                  if k = 1 then 
                    begin
                      x1 := xtmp;
                      y1 := ytmp;
                    end;
                  if k = 2 then 
                    begin
                      x2 := xtmp;
                      y2 := ytmp;
                    end;
                  if k = 3 then 
                    begin
                      x3 := xtmp;
                      y3 := ytmp;
                    end;
                  if k = 4 then 
                    begin
                      x4 := xtmp;
                      y4 := ytmp;
                    end;
                end;
              inc(k);
            end;  // while
        end;
    end;
end;


procedure read_sgroups(sgroupstring:string);
var
  i, n_sg, sg_id, sg_index : integer;
  n_tmp, a, a1, a2, b : integer;
  xtmp, ytmp : single;
  sg_type : string;
  tmpstr : string;
begin
  if (pos('M  STY',sgroupstring) > 0) then
    begin
      delete(sgroupstring,1,6);
      left_trim(sgroupstring);
      n_sg := left_int(sgroupstring);  // this assignment must be kept also in non-debug mode!
      for i := 1 to n_sg do
        begin
          sg_id := left_int(sgroupstring);
          left_trim(sgroupstring);
          if length(sgroupstring) >= 3 then
            begin
              sg_type := copy(sgroupstring,1,3);
              delete(sgroupstring,1,3);
              if ((sg_type = 'SUP') or (sg_type = 'DAT')) and (n_sgroups < max_sgroups) then
                begin
                  inc(n_sgroups);
                  sgroup^[n_sgroups].id := sg_id;
                  sgroup^[n_sgroups].sgtype := sg_type;
                end;
              if (sg_type = 'SRU') and (n_brackets < max_brackets) then
                begin
                  inc(n_brackets);
                  bracket^[n_brackets].id := sg_id;
                  bracket^[n_brackets].x1 := 0;
                  bracket^[n_brackets].y1 := 0;
                  bracket^[n_brackets].x2 := 0;
                  bracket^[n_brackets].y2 := 0;
                  bracket^[n_brackets].x3 := 0;
                  bracket^[n_brackets].y3 := 0;
                  bracket^[n_brackets].x4 := 0;
                  bracket^[n_brackets].y4 := 0;
                  //bracket^[n_brackets].brtype := what??;
                end;
            end;
        end;

    end;
  if (pos('M  SMT',sgroupstring) > 0) then
    begin
      delete(sgroupstring,1,6);
      left_trim(sgroupstring);
      sg_id := left_int(sgroupstring);  // this assignment must be kept also in non-debug mode!
      left_trim(sgroupstring);
      sg_index := get_sgroup_index(sg_id);
      if (sg_index > 0) then sgroup^[sg_index].sglabel := sgroupstring;
      sg_index := get_bracket_index(sg_id);
      if (sg_index > 0) then bracket^[sg_index].brlabel := sgroupstring;
    end;
  if (pos('M  SDD',sgroupstring) > 0) then
    begin
      tmpstr := copy(sgroupstring,8,3);
      sg_id := left_int(tmpstr);
      sg_index := get_sgroup_index(sg_id);
      if (sg_index > 0) then
        begin
          tmpstr := copy(sgroupstring,12,10);
          xtmp := left_float(tmpstr);
          tmpstr := copy(sgroupstring,22,10);
          ytmp := left_float(tmpstr);
          sgroup^[sg_index].x := xtmp;
          sgroup^[sg_index].y := ytmp;
          sgroup^[sg_index].justification := 'L';  // preliminary (maybe "centered" would look better
          //with sgroup^[sg_index] do writeln('% ',sg_index,':  x = ',x:1:5,', y = ',y:1:5,' label = ',sglabel);
        end;
    end;
  if (pos('M  SED',sgroupstring) > 0) then
    begin
      delete(sgroupstring,1,6);
      left_trim(sgroupstring);
      sg_id := left_int(sgroupstring);  // this assignment must be kept also in non-debug mode!
      left_trim(sgroupstring);
      sg_index := get_sgroup_index(sg_id);
      if (sg_index > 0) then sgroup^[sg_index].sglabel := sgroupstring;
      //with sgroup^[sg_index] do writeln('% ',sg_index,':  x = ',x:1:5,', y = ',y:1:5,' label = ',sglabel);
    end;
  if (pos('M  SAL',sgroupstring) > 0) then
    begin
      delete(sgroupstring,1,6);
      left_trim(sgroupstring);
      sg_id := left_int(sgroupstring);
      n_tmp := left_int(sgroupstring);
      sg_index := get_sgroup_index(sg_id);
      if (sg_index > 0) and (sgroup^[sg_index].sgtype = 'SUP') then
        begin      
          for i := 1 to n_tmp do
            begin
              a := left_int(sgroupstring);
              if (a > 0) and (a <= n_atoms) then atom^[a].sg := true;
            end;
        end;
    end;    
  if (pos('M  SBV',sgroupstring) > 0) then
    begin
      delete(sgroupstring,1,6);
      left_trim(sgroupstring);
      sg_id := left_int(sgroupstring);
      sg_index := get_sgroup_index(sg_id);
      b := left_int(sgroupstring);
      if (b > 0) and (b <= n_bonds) then
        begin
          a1 := bond^[b].a1;
          a2 := bond^[b].a2;
          if (atom^[a1].sg = true) then sgroup^[sg_index].anchor := a1;  // one of these two atoms should
          if (atom^[a2].sg = true) then sgroup^[sg_index].anchor := a2;  // be _not_ in the Sgroup
          xtmp := left_float(sgroupstring);
          ytmp := left_float(sgroupstring);
          if (xtmp <= 0) then sgroup^[sg_index].justification := 'L' else
                              sgroup^[sg_index].justification := 'R';
        end;
    end;    
end;


procedure read_MDLmolfile(mfilename:string);  // reads MDL mol files
var
  n, code : integer;
  rline, tmpstr : string;
  xstr, ystr, zstr, chgstr : string;
  xval, yval, zval, chgval : single;
  a1str, a2str, elemstr : string;
  a1val, a2val : integer;
  ri, rc, bt,bs : integer;
  sepcount : integer;
  i : integer;              // new in mol2ps
  clearcharges : boolean;   // new in mol2ps
  mstr : string;            // v0.3a
  mval : integer;           // v0.3a
begin
  clearcharges := true;     // new in mol2ps
  if n_atoms > 0 then zap_molecule;
  rline := '';
  ri := li;
  molname := molbuf^[ri];            // line 1
  if ri < molbufindex then inc(ri);  // line 2
  rline   := molbuf^[ri];
  if ri < molbufindex then inc(ri);  // line 3
  rline   := molbuf^[ri];
  //molcomment := rline;
  if ri < molbufindex then inc(ri);  // line 4
  rline := molbuf^[ri];
  tmpstr := copy(rline,1,3);
  val(tmpstr,n_atoms,code);
  tmpstr := copy(rline,4,3);
  val(tmpstr,n_bonds,code);
  try
    getmem(atom,n_atoms*sizeof(atom_rec));
    getmem(bond,n_bonds*sizeof(bond_rec));
    getmem(ring,sizeof(ringlist));
    getmem(ringprop,sizeof(ringprop_type));
    getmem(bracket,sizeof(bracket_type));  // v0.1f
    getmem(sgroup,sizeof(sgroup_type));  // v0.2a
  except
    on e:Eoutofmemory do
      begin
        writeln('Not enough memory');
        close(molfile);
        halt(4);
        exit;
      end;
  end;
  n_heavyatoms := 0;
  n_heavybonds := 0;
  for n := 1 to n_atoms do
    begin
      with atom^[n] do
        begin
          x := 0; y := 0; z := 0;
          formal_charge  := 0;
          real_charge    := 0;
          Hexp           := 0;
          Htot           := 0;
          neighbor_count := 0;
          ring_count     := 0;
          arom           := FALSE;
          stereo_care    := false;
          metal          := false;
          heavy          := false;
          tag            := false;
          nucleon_number := 0;
          radical_type   := 0;
          sg             := false;
          alias          := '';  // v0.2b
          a_just         := 0;   // v0.2b
          map_id         := 0;   // v0.3a
        end;
      if ri < molbufindex then 
        begin
          inc(ri);  // v0.2b
          rline := molbuf^[ri];
          atomtype := copy(rline,32,3);
          elemstr  := get_MDLelement(atomtype);
          newatomtype := convert_MDLtype(atomtype);
          xstr := copy(rline,2,9);
          ystr := copy(rline,12,9);
          zstr := copy(rline,22,9);
          chgstr := copy(rline,37,3);  // new in mol2ps v0.1
          mstr := copy(rline,61,3);    // v0.3a
          val(xstr,xval,code);
          val(ystr,yval,code);
          val(zstr,zval,code);
          val(chgstr,chgval,code);
          val(mstr,mval,code);
          if (chgval <> 0) then
            begin
              if (chgval >= 1) and (chgval <= 7) then
                chgval := 4 - chgval else chgval := 0;
            end;                        // end
          with atom^[n] do
            begin
              element := elemstr;
              atype := newatomtype;
              x := xval; y := yval; z := zval; formal_charge := round(chgval); real_charge := 0;
              x_orig := xval; y_orig := yval; z_orig := zval;
              // read aromaticity flag from CheckMol-tweaked MDL molfile
              if (length(rline) > 37) and (rline[38] = '0') then
                begin
                  arom := true;
                end;
              if (length(rline) > 47) and (rline[48] = '1') then stereo_care := true;
              if (is_heavyatom(n)) then 
                begin
                  inc(n_heavyatoms);
                  heavy := true;
                  if is_metal(n) then metal := true;
                end;
              nvalences := get_nvalences(element);  // v0.3m                
              map_id := mval;  // v0.3a
            end;
       end;
    end;
  for n := 1 to n_bonds do
    begin
      if ri < molbufindex then 
        begin
          inc(ri);  // v0.2b
          rline := molbuf^[ri];
          a1str := copy(rline,1,3);
          a2str := copy(rline,4,3);
          val(a1str,a1val,code);
          if code <> 0 then beep;
          val(a2str,a2val,code);
          if code <> 0 then beep;
          with bond^[n] do
            begin
              a1 := a1val; a2 := a2val;
              if rline[9] = '1' then btype := 'S';  // single
              if rline[9] = '2' then btype := 'D';  // double
              if rline[9] = '3' then btype := 'T';  // triple
              if rline[9] = '4' then btype := 'A';  // aromatic
              if rline[9] = '5' then btype := 'l';  // single or double
              if rline[9] = '6' then btype := 's';  // single or aromatic
              if rline[9] = '7' then btype := 'd';  // double or aromatic
              if rline[9] = '8' then btype := 'a';  // any
              bsubtype := 'N';   // mol2ps
              a_handle := 0;     // mol2ps
              arom := false;
              // read aromaticity flag from CheckMol-tweaked MDL molfile
              if (btype = 'A') or (rline[8] = '0') then
                begin
                  arom := true;
                end;
              tmpstr := copy(rline,13,3);  // read ring_count from tweaked molfile
              val(tmpstr,rc,code);
              tmpstr := copy(rline,16,3);  // read bond topology;
              val(tmpstr,bt,code);         // extended features are encoded by leading zero
              if ((code <> 0) or (bt < 0) or (bt > 5)) then topo := btopo_any else 
                begin
                  if (tmpstr[2] = '0') then topo := bt + 3 else topo := bt;
                end;
              // stereo property from MDL "stereo care" flag in atom block
              stereo := bstereo_any;
              if (btype ='D') then
                begin
                  if (atom^[a1].stereo_care and atom^[a2].stereo_care) then
                    begin                      // this is the MDL-conformant encoding,
                      stereo := bstereo_xyz;   // for an alternative see below
                    end else
                    begin
                      tmpstr := copy(rline,10,3);  // read bond stereo specification;
                      val(tmpstr,bs,code);         // this extended feature is encoded by a leading zero
                      if ((code <> 0) or (bs <= 0) or (bs > 2)) then stereo := bstereo_any 
                        else stereo := bstereo_xyz;
                      if (tmpstr[2] = '0') then stereo := bstereo_xyz;
                    end;
                end;
              //if stereo <> bstereo_any then ez_search := true;
              if (btype ='S') and (length(rline)>11) and (rline[12]='1') then stereo := bstereo_up;
              if (btype ='S') and (length(rline)>11) and (rline[12]='6') then stereo := bstereo_down;
              tmpstr := copy(rline,10,3);  // new in v0.1c: save original bond stereo specification;
              val(tmpstr,bs,code);         // v0.1c
              mdl_stereo := bs;            // v0.1c
              {$IFDEF csearch_extensions}
              if ((btype = 'S') and (mdl_stereo = 4)) then btype := 'C';  // v0.1c  complex bonds
              {$ENDIF}
              sg := false;
            end;
          if is_heavyatom(a1val) and is_heavyatom(a2val) then inc(n_heavybonds);
      end;
    end;
  fillchar(bracket^,sizeof(bracket_type),0);  // v0.1f
  n_brackets := 0;  // v0.1f
  fillchar(sgroup^,sizeof(sgroup_type),0);  // v0.2a
  n_sgroups := 0;  // v0.2a
  sepcount := 0;
  while (ri < molbufindex) and (sepcount < 1) do
    begin
      inc(ri);
      rline := molbuf^[ri];
      if (pos('M  CHG',rline) > 0) then 
        begin
          if clearcharges then  // "M  CHG" supersedes all "old-style" charge values
            begin
              for i := 1 to n_atoms do atom^[i].formal_charge := 0;
            end;
          read_charges(rline);
          clearcharges := false;  // subsequent "M  CHG" lines must not clear previous values
        end;  
      if (pos('A  ',rline) = 1) then   // v0.2b
        begin
          tmpstr := '';
          if ri < molbufindex then inc(ri);  // line 2
          tmpstr := molbuf^[ri];
          read_alias(rline,tmpstr);
        end;
      if (pos('M  ISO',rline) > 0) then read_isotopes(rline);
      if (pos('M  RAD',rline) > 0) then read_radicals(rline);
      if (pos('M  SDI',rline) > 0) then read_brackets(rline);
      if (pos('M  STY',rline) > 0) 
        or (pos('M  SAL',rline) > 0)
        or (pos('M  SMT',rline) > 0) 
        or (pos('M  SBV',rline) > 0)
        or (pos('M  SDD',rline) > 0)
        or (pos('M  SED',rline) > 0)  then read_sgroups(rline);
      if (pos('$$$$',rline)>0) then
        begin
          inc(sepcount);
          if (molbufindex > (ri + 2)) then mol_in_queue := true;  // we assume this is an SDF file
        end;
    end;
  fillchar(ring^,sizeof(ringlist),0);
  for n := 1 to max_rings do
    begin
      ringprop^[n].size     := 0;
      ringprop^[n].arom     := false;
      ringprop^[n].envelope := false;
    end;
  li := ri + 1;
end;

//============= chemical processing functions & procedures ============

function nvalences(a_el:str2):integer;
// preliminary version; should be extended to element/atomtype
var
  res : integer;
begin
  res := 1;
  if a_el = 'H ' then res := 1;
  if a_el = 'C ' then res := 4;
  if a_el = 'N ' then res := 3;
  if a_el = 'O ' then res := 2;
  if a_el = 'S ' then res := 2;
  if a_el = 'SE' then res := 2;
  if a_el = 'TE' then res := 2;
  if a_el = 'P ' then res := 3;
  if a_el = 'F ' then res := 1;
  if a_el = 'CL' then res := 1;
  if a_el = 'BR' then res := 1;
  if a_el = 'I ' then res := 1;
  if a_el = 'B ' then res := 3;
  if a_el = 'LI' then res := 1;
  if a_el = 'NA' then res := 1;
  if a_el = 'K ' then res := 1;
  if a_el = 'CA' then res := 2;
  if a_el = 'SR' then res := 2;
  if a_el = 'MG' then res := 2;
  if a_el = 'FE' then res := 3;
  if a_el = 'MN' then res := 2;
  if a_el = 'HG' then res := 2;
  if a_el = 'SI' then res := 4;
  if a_el = 'SN' then res := 4;
  if a_el = 'ZN' then res := 2;
  if a_el = 'CU' then res := 2;
  if a_el = 'A ' then res := 4;
  if a_el = 'Q ' then res := 4;
  nvalences := res;
end;


function is_electroneg(a_el:str2):boolean;
var
  res : boolean;
begin
  res := false;;
  if a_el = 'N ' then res := true;
  if a_el = 'P ' then res := true;
  if a_el = 'O ' then res := true;
  if a_el = 'S ' then res := true;
  if a_el = 'SE' then res := true;
  if a_el = 'TE' then res := true;
  if a_el = 'F ' then res := true;
  if a_el = 'CL' then res := true;
  if a_el = 'BR' then res := true;
  if a_el = 'I ' then res := true;
  if a_el = 'SI' then res := true;  // v0.f
  is_electroneg := res;
end;


procedure count_neighbors;
// counts heavy-atom neighbors and explicit hydrogens
var
  i : integer;
begin
  if (n_atoms < 1) or (n_bonds < 1) then exit;
  for i := 1 to n_bonds do
    begin
      if is_heavyatom(bond^[i].a1) then inc(atom^[(bond^[i].a2)].neighbor_count);
      if is_heavyatom(bond^[i].a2) then inc(atom^[(bond^[i].a1)].neighbor_count);
      if (atom^[(bond^[i].a1)].element = 'H ') then inc(atom^[(bond^[i].a2)].Hexp);
      if (atom^[(bond^[i].a2)].element = 'H ') then inc(atom^[(bond^[i].a1)].Hexp);
      // plausibility check
      if (atom^[(bond^[i].a1)].neighbor_count > max_neighbors) or 
         (atom^[(bond^[i].a2)].neighbor_count > max_neighbors) then
         begin
           mol_OK := false;
           //writeln('invalid molecule!');
         end;
    end;
end;


function get_neighbors(id:integer):neighbor_rec;
var
  i : integer;
  nb_tmp : neighbor_rec;
  nb_count : integer;
begin
  fillchar(nb_tmp,sizeof(neighbor_rec),0);
  nb_count := 0;
  for i := 1 to n_bonds do
    begin
      if ((bond^[i].a1 = id) and (nb_count < max_neighbors)) and (is_heavyatom(bond^[i].a2)) then
        begin
          inc(nb_count);
          nb_tmp[nb_count] := bond^[i].a2;
        end;
      if ((bond^[i].a2 = id) and (nb_count < max_neighbors)) and (is_heavyatom(bond^[i].a1)) then
        begin
          inc(nb_count);
          nb_tmp[nb_count] := bond^[i].a1;
        end;
    end;
  get_neighbors := nb_tmp;
end;


function get_nextneighbors(id:integer;prev_id:integer):neighbor_rec;
var
  i : integer;
  nb_tmp : neighbor_rec;
  nb_count : integer;
begin
  // gets all neighbors except prev_id (usually the atom where we came from
  fillchar(nb_tmp,sizeof(neighbor_rec),0);
  nb_count := 0;
  for i := 1 to n_bonds do
    begin
      if ((bond^[i].a1 = id) and (bond^[i].a2 <> prev_id) and (nb_count < max_neighbors)) 
        and (is_heavyatom(bond^[i].a2)) then
        begin
          inc(nb_count);
          nb_tmp[nb_count] := bond^[i].a2;
        end;
      if ((bond^[i].a2 = id) and (bond^[i].a1 <> prev_id) and (nb_count < max_neighbors)) 
        and (is_heavyatom(bond^[i].a1)) then
        begin
          inc(nb_count);
          nb_tmp[nb_count] := bond^[i].a1;
        end;
    end;
  get_nextneighbors := nb_tmp;
end;


function get_allneighbors(id:integer):neighbor_rec;  // v0.1f
var
  i : integer;
  nb_tmp : neighbor_rec;
  nb_count : integer;
begin
  fillchar(nb_tmp,sizeof(neighbor_rec),0);
  nb_count := 0;
  for i := 1 to n_bonds do
    begin
      if ((bond^[i].a1 = id) and (nb_count < max_neighbors)) then
        begin
          inc(nb_count);
          nb_tmp[nb_count] := bond^[i].a2;
        end;
      if ((bond^[i].a2 = id) and (nb_count < max_neighbors)) then
        begin
          inc(nb_count);
          nb_tmp[nb_count] := bond^[i].a1;
        end;
    end;
  get_allneighbors := nb_tmp;
end;


function path_pos(id:integer;a_path:ringpath_type):integer;
var
  i, pp : integer;
begin
  pp := 0;
  for i := max_ringsize downto 1 do
    begin
      if (a_path[i] = id) then pp := i;
    end;
  path_pos := pp;
end;


function path_length(a_path:ringpath_type):integer;
begin
  if (a_path[max_ringsize] <> 0) and (path_pos(0,a_path)=0) then path_length := max_ringsize else
    begin
      path_length := path_pos(0,a_path)-1;
    end;
end;


function get_bond(ba1,ba2:integer):integer;
var
  i, b_id : integer;
begin
  b_id := 0;
  if n_bonds > 0 then begin
    for i := 1 to n_bonds do
      begin
        if ((bond^[i].a1 = ba1) and (bond^[i].a2 = ba2)) or
           ((bond^[i].a1 = ba2) and (bond^[i].a2 = ba1)) then
           b_id := i;
      end;
  end;
  get_bond := b_id;
end;


procedure order_ringpath(var r_path:ringpath_type);
// order should be: array starts with atom of lowest number, followed by neighbor atom with lower number
var
  i, pl : integer;
  a_ref, a_left, a_right, a_tmp : integer;
begin
  pl := path_length(r_path);
  if (pl < 3) then exit;
  a_ref := n_atoms;  // start with highest possible value for an atom number
  for i := 1 to pl do
    begin
      if r_path[i] < a_ref then a_ref := r_path[i];  // find the minimum value ==> reference atom
    end;
  if a_ref < 1 then exit;  // just to be sure
  if path_pos(a_ref,r_path) < pl then a_right := r_path[(path_pos(a_ref,r_path)+1)] else a_right := r_path[1];
  if path_pos(a_ref,r_path) > 1 then a_left := r_path[(path_pos(a_ref,r_path)-1)] else a_left := r_path[pl];
  if a_right = a_left then exit;  // should never happen
  if a_right < a_left then
    begin  // correct ring numbering direction, only shift of the reference atom to the left end required
      while path_pos(a_ref,r_path) > 1 do
        begin
          a_tmp := r_path[1];
          for i := 1 to (pl - 1) do r_path[i] := r_path[(i+1)];
          r_path[pl] := a_tmp;
        end;
    end else
    begin  // wrong ring numbering direction, two steps required
      while path_pos(a_ref,r_path) < pl do
        begin  // step one: create "mirrored" ring path with reference atom at right end
          a_tmp := r_path[pl];
          for i := pl downto 2 do r_path[i] := r_path[(i-1)];
          r_path[1] := a_tmp;
        end;
      for i := 1 to (pl div 2) do
        begin  // one more mirroring
          a_tmp := r_path[i];
          r_path[i] := r_path[(pl+1)-i];
          r_path[(pl+1)-i] := a_tmp;
        end;
    end;
end;


function ringcompare(rp1,rp2:ringpath_type):integer;
var
  i, j, rc, rs1, rs2 : integer;
  n_common, max_cra : integer;
begin
  rc := 0;
  n_common := 0;
  rs1 := path_length(rp1);
  rs2 := path_length(rp2);
  if rs1 < rs2 then max_cra := rs1 else max_cra := rs2;
  for i := 1 to rs1 do
    for j := 1 to rs2 do
      if rp1[i] = rp2[j] then inc(n_common);
  if (rs1 = rs2) and (n_common = max_cra) then rc := 0 else
    begin
      if n_common = 0 then inc(rc,8);
      if n_common < max_cra then inc(rc,4) else
        begin
          if rs1 < rs2 then inc(rc,1) else inc(rc,2);
        end;
    end;
  ringcompare := rc;
end;


function rc_identical(rc_int:integer):boolean;
begin
  if rc_int = 0 then rc_identical := true else rc_identical := false;
end;


function rc_1in2(rc_int:integer):boolean;
begin
  if odd(rc_int) then rc_1in2 := true else rc_1in2 := false;
end;


function rc_2in1(rc_int:integer):boolean;
begin
  rc_int := rc_int div 2;
  if odd(rc_int) then rc_2in1 := true else rc_2in1 := false;
end;


function rc_different(rc_int:integer):boolean;
begin
  rc_int := rc_int div 4;
  if odd(rc_int) then rc_different := true else rc_different := false;
end;


function rc_independent(rc_int:integer):boolean;
begin
  rc_int := rc_int div 8;
  if odd(rc_int) then rc_independent := true else rc_independent := false;
end;


function is_newring(n_path:ringpath_type):boolean;
var
  i, j : integer;
  nr, same_ring : boolean;
  tmp_path : ringpath_type;
  rc_result : integer;
  found_ring : boolean;
  pl : integer;
begin
  nr := true;
  pl := path_length(n_path);
  if n_rings > 0 then
    begin
      case ringsearch_mode of
        rs_sar  : begin
                    found_ring := false;
                    i := 0;
                    while ((i < n_rings) and (not found_ring)) do
                      begin
                        inc(i);
                        if (pl = ringprop^[i].size) then  // compare only rings of same size
                          begin  
                            same_ring := true;   
                            for j := 1 to max_ringsize do
                              begin
                                if (ring^[i,j] <> n_path[j]) then same_ring := false;
                              end;
                            if same_ring then 
                              begin
                                nr := false;
                                found_ring := true;
                              end;
                          end;
                      end;  // while
                  end;
        rs_ssr  : begin
                    for i := 1 to n_rings do
                      begin
                        for j := 1 to max_ringsize do tmp_path[j] := ring^[i,j];
                        rc_result := ringcompare(n_path,tmp_path);
                        if rc_identical(rc_result) then nr := false;
                        if rc_1in2(rc_result) then
                          begin
                            // exchange existing ring by smaller one
                            for j := 1 to max_ringsize do ring^[i,j] := n_path[j];
                            // update ring property record
                            ringprop^[i].size := pl;
                            nr := false;
                            {$IFDEF debug}
                            debugoutput('replacing ring '+inttostr(i)+' by smaller one (ringsize: '+inttostr(path_length(n_path))+')');
                            {$ENDIF}
                          end;
                        if rc_2in1(rc_result) then
                          begin
                            // new ring contains existing one, but is larger ==> discard
                            nr := false;
                          end;
                      end;
                  end;
      end;  // case
    end;
  is_newring := nr;
end;


procedure add_ring(n_path:ringpath_type);
// store rings in an ordered way (with ascending ring size)
var
  i, j, k, s, pl : integer;
begin
  pl := path_length(n_path);
  if pl < 1 then exit;
  if n_rings < max_rings then
    begin
      inc(n_rings);
      j := 0;
      if (n_rings > 1) then
        begin
          for i := 1 to (n_rings - 1) do
            begin
              s := ringprop^[i].size;
              if (pl >= s) then j := i;
            end;
        end;
      inc(j);  // the next position is ours
      if (j < n_rings) then
        begin  // push up the remaining rings by one position
          for k := n_rings downto (j+1) do
            begin
              ringprop^[k].size := ringprop^[(k-1)].size;
              for i := 1 to max_ringsize do
                begin
                  ring^[k,i] := ring^[(k-1),i];
                end; 
            end;
        end;
      ringprop^[j].size := pl;  
      for i := 1 to max_ringsize do
        begin
          ring^[j,i] := n_path[i];
        end; 
    end else 
    begin
      {$IFDEF debug}
      debugoutput('max_rings exceeded!');
      {$ENDIF}
    end;  
end;


function is_ringpath(s_path:ringpath_type):boolean;
var
  i, j : integer;
  nb : neighbor_rec;
  rp, new_atom : boolean;
  a_last, pl : integer;
  l_path : ringpath_type;
begin
  rp := false;
  new_atom := false;
  fillchar(nb,sizeof(neighbor_rec),0);
  fillchar(l_path,sizeof(ringpath_type),0);
  pl := path_length(s_path);
  if pl < 1 then 
    begin 
      {$IFDEF debug}
      debugoutput('Oops! Got zero-length s_path!'); 
      {$ENDIF}
      exit; 
    end;
  for i := 1 to pl do
    begin
      l_path[i] := s_path[i];
    end;
  // check if the last atom is a metal and stop if opt_metalrings is not set (v0.3)
  if (opt_metalrings = false) then
    begin
      if atom^[l_path[pl]].metal then
        begin
          {$IFDEF debug}
          debugoutput('skipping metal in ring search'); 
          {$ENDIF}
          is_ringpath := false;
          exit;
        end;
    end;
  // check if ring is already closed
  if (pl > 2) and (l_path[pl] = l_path[1]) then
    begin
      l_path[pl] := 0;  // remove last entry (redundant!)
      order_ringpath(l_path);
      if is_newring(l_path) then
        begin
          if (n_rings < max_rings) then add_ring(l_path) else
            begin
              {$IFDEF debug}
              debugoutput('maximum number of rings exceeded!');
              {$ENDIF}
              is_ringpath := false;
              exit;
            end;
        end;
      rp := true;
      is_ringpath := true;
      exit;
    end;
  // any other case: ring is not (yet) closed
  a_last := l_path[pl];
  nb := get_neighbors(a_last);
  if atom^[a_last].neighbor_count > 1 then
    begin
      if ((rp = false) and (n_rings < max_rings)) then   // added in v0.2: check if max_rings is reached
        begin  // if ring is not closed, continue searching
          for i := 1 to atom^[a_last].neighbor_count do
            begin
              new_atom := true;
              for j := 2 to pl do if nb[i] = l_path[j] then 
                begin      // v0.3k
                  new_atom := false;
                  break;   // v0.3k
                end;
              // added in v0.1a: check if max_rings not yet reached
              // added in v0.2:  limit ring size to max_vringsize instead of max_ringsize
              if (new_atom) and (pl < max_vringsize) and (n_rings < max_rings) then
                begin
                  l_path[(pl+1)] := nb[i];
                  if (pl < max_ringsize-1) then l_path[pl+2] := 0;  // just to be sure
                  inc(recursion_level);                             // 
                  if (recursion_level > max_recursion_depth) then
                    begin
                      n_rings := max_rings;
                      is_ringpath := false;
                      exit;
                    end;                                            // 
                  if is_ringpath(l_path) then rp := true;
                end;
            end;
        end;
    end;
  is_ringpath := rp;
end;


function is_ringbond(b_id:integer):boolean;
var
  i : integer;
  ra1, ra2 : integer;
  nb : neighbor_rec;
  search_path : ringpath_type;
  rb : boolean;
begin
  rb := false;
  recursion_level := 0; 
  ra1 := bond^[b_id].a1;
  ra2 := bond^[b_id].a2;
  fillchar(nb,sizeof(neighbor_rec),0);
  fillchar(search_path,sizeof(ringpath_type),0);
  nb := get_neighbors(ra2);
  if (atom^[ra2].neighbor_count > 1) and (atom^[ra1].neighbor_count > 1) then
    begin
      search_path[1] := ra1;
      search_path[2] := ra2;
      for i := 1 to atom^[ra2].neighbor_count do
        begin
          if (nb[i] <> ra1) and (atom^[nb[i]].heavy) then
            begin
              search_path[3] := nb[i];
              if is_ringpath(search_path) then rb := true;
            end;
        end;
    end;
  is_ringbond := rb;
end;


procedure chk_ringbonds;
var
  i : integer;
  a1rc, a2rc : integer;
begin
  if n_bonds < 1 then exit;
  for i := 1 to n_bonds do
    begin
      a1rc := atom^[(bond^[i].a1)].ring_count;
      a2rc := atom^[(bond^[i].a2)].ring_count;
      if ((n_rings = 0) or ((a1rc < n_rings) and (a2rc < n_rings) )) then
        begin
          if is_ringbond(i) then
            begin
              //inc(bond^[i].ring_count);
            end;
        end;
    end;
end;


function is_oxo_C(id:integer):boolean;
var
  i  : integer;
  r  : boolean;
  nb : neighbor_rec;
begin
  r := false;
  fillchar(nb,sizeof(neighbor_rec),0);
  if (id < 1) or (id > n_atoms) then exit;
  nb := get_neighbors(id);
  if (atom^[id].element = 'C ') and (atom^[id].neighbor_count > 0) then
    begin
      for i := 1 to atom^[id].neighbor_count do
        begin
          if (bond^[get_bond(id,nb[i])].btype = 'D') and
             ((atom^[(nb[i])].element = 'O ') { or
              (atom^[(nb[i])].element = 'S ')  or
              (atom^[(nb[i])].element = 'SE') } ) then     // no N, amidines are different...
             r := true;
        end;
    end;
  is_oxo_C := r;
end;


function is_thioxo_C(id:integer):boolean;
var
  i  : integer;
  r  : boolean;
  nb : neighbor_rec;
begin
  r := false;
  fillchar(nb,sizeof(neighbor_rec),0);
  if (id < 1) or (id > n_atoms) then exit;
  nb := get_neighbors(id);
  if (atom^[id].element = 'C ') and (atom^[id].neighbor_count > 0) then
    begin
      for i := 1 to atom^[id].neighbor_count do
        begin
          if (bond^[get_bond(id,nb[i])].btype = 'D') and
             ((atom^[(nb[i])].element = 'S ')  or
              (atom^[(nb[i])].element = 'SE')) then     // no N, amidines are different...
             r := true;
        end;
    end;
  is_thioxo_C := r;
end;


function is_exocyclic_imino_C(id,r_id:integer):boolean;
var
  i,j  : integer;
  r    : boolean;
  nb   : neighbor_rec;
  testring : ringpath_type;
  ring_size : integer;
begin
  r := false;
  fillchar(nb,sizeof(neighbor_rec),0);
  if (id < 1) or (id > n_atoms) then exit;
  nb := get_neighbors(id);
  fillchar(testring,sizeof(ringpath_type),0);
  for j := 1 to max_ringsize do if ring^[r_id,j] > 0 then testring[j] := ring^[r_id,j];
  ring_size := path_length(testring);
  if (atom^[id].element = 'C ') and (atom^[id].neighbor_count > 0) then
    begin
      for i := 1 to atom^[id].neighbor_count do
        begin
          if (bond^[get_bond(id,nb[i])].btype = 'D') and
             (atom^[(nb[i])].element = 'N ') then
               begin
                 r := true;
                 for j := 1 to ring_size do
                   if nb[i] = ring^[r_id,j] then r := false;
               end;
        end;
    end;
  is_exocyclic_imino_C := r;
end;


function find_exocyclic_methylene_C(id,r_id:integer):integer; 
var                    // renamed and rewritten in v0.3j
  i,j  : integer;
  r    : integer;
  nb   : neighbor_rec;
  testring : ringpath_type;
  ring_size : integer;
begin
  r := 0;
  fillchar(nb,sizeof(neighbor_rec),0);
  if (id < 1) or (id > n_atoms) then 
    begin
      find_exocyclic_methylene_C := 0;
      exit;
    end;
  nb := get_neighbors(id);
  fillchar(testring,sizeof(ringpath_type),0);
  for j := 1 to max_ringsize do if ring^[r_id,j] > 0 then testring[j] := ring^[r_id,j];
  ring_size := path_length(testring);
  if (atom^[id].element = 'C ') and (atom^[id].neighbor_count > 0) then
    begin
      for i := 1 to atom^[id].neighbor_count do
        begin
          if (bond^[get_bond(id,nb[i])].btype = 'D') and
             (atom^[(nb[i])].element = 'C ') then
               begin
                 r := nb[i];
                 for j := 1 to ring_size do
                   if nb[i] = ring^[r_id,j] then r := 0;
               end;
        end;
    end;
  find_exocyclic_methylene_C := r;
end;


function is_methylC(a1:integer): boolean;
var
  res : boolean;
  nb : neighbor_rec;
  a2, b : integer;
begin
  res := false;
  if (atom^[a1].atype = 'C3 ') and (atom^[a1].neighbor_count = 1) then
    begin
      nb := get_neighbors(a1);
      a2 := nb[1];
      b := get_bond(a1,a2);
      if bond^[b].btype = 'S' then res := true;
    end;
  is_methylC := res;  
end;


function is_diazonium(a_view,a_ref:integer):boolean;
var
  r  : boolean;
  nb : neighbor_rec;
  bond_count : integer;
  chg_count : integer;
  n1, n2 : integer;
begin
  r := false;
  bond_count := 0;
  chg_count := 0;
  n1 := 0; n2 := 0;
  if (is_heavyatom(a_view)) and (bond^[get_bond(a_view,a_ref)].btype = 'S') then
    begin
      if (atom^[a_ref].element = 'N ') and (atom^[a_ref].neighbor_count = 2) then
        begin
          n1 := a_ref;
          chg_count := atom^[n1].formal_charge;
          fillchar(nb,sizeof(neighbor_rec),0);
          nb := get_nextneighbors(n1,a_view);
          if (atom^[(nb[1])].element = 'N ') then
            begin
              n2 := nb[1];
              chg_count := chg_count + atom^[n2].formal_charge;                      
              if (bond^[get_bond(n1,n2)].btype = 'S') then inc(bond_count);
              if (bond^[get_bond(n1,n2)].btype = 'D') then inc(bond_count,2);
              if (bond^[get_bond(n1,n2)].btype = 'T') then inc(bond_count,3);
            end;
          if (n1 > 0) and (n2 > 0) and (atom^[n2].neighbor_count = 1) and
             (bond_count >= 2) and (chg_count > 0) then r := true
        end;
    end;
  is_diazonium := r;
end;


procedure update_Htotal;
var
  i, j, b_id : integer;
  nb : neighbor_rec;
  single_count, double_count, triple_count, arom_count : integer;
  total_bonds : integer;
  Htotal : integer;
  nval   : integer;   // new in v0.3
  diazon : boolean;       // new in v0.3j
  nb2    : neighbor_rec;  // new in v0.3j
  a1, a2, a3 : integer;   // new in v0.3j
begin
  if n_atoms < 1 then exit;
  diazon := false;
  fillchar(nb,sizeof(neighbor_rec),0);
  for i := 1 to n_atoms do
    begin
      single_count := 0;
      double_count := 0;
      triple_count := 0;
      arom_count   := 0;
      total_bonds  := 0;
      Htotal    := 0;
      nb := get_neighbors(i);
      if atom^[i].neighbor_count > 0 then
        begin  // count single, double, triple, and aromatic bonds to all neighbor atoms
          for j := 1 to atom^[i].neighbor_count do
            begin
              b_id := get_bond(i,nb[j]);
              if b_id > 0 then
                begin
                  if bond^[b_id].btype = 'S' then inc(single_count);
                  if bond^[b_id].btype = 'D' then inc(double_count);
                  if bond^[b_id].btype = 'T' then inc(triple_count);
                  if bond^[b_id].btype = 'A' then inc(arom_count);
                  if bond^[b_id].btype = 'a' then inc(single_count);  // v0.2b, treat "any" as "single"
                end;
            end;
          //check for diazonium salts
          a1 := i; a2 := nb[1];
          if (atom^[a1].element = 'N ') and (atom^[a2].element = 'N ') then
            begin
              if (atom^[a2].neighbor_count = 2) then
                begin
                  nb2 := get_nextneighbors(a2,a1);
                  a3 := nb2[1];
                  if (atom^[a3].element = 'C ') and is_diazonium(a3,a2) then diazon := true;
                end;
            end;
        end;
      total_bonds := single_count + 2*double_count + 3*triple_count + trunc(1.5*arom_count);  
      // calculate number of total hydrogens per atom
      //nval := nvalences(atom^[i].element);    // new in v0.3
      nval := atom^[i].nvalences;    // new in v0.3m
      if (atom^[i].element = 'P ') then 
        begin
          if ((total_bonds - atom^[i].formal_charge) > 3) then nval := 5;  // refined in v0.3n
        end;                                  // 
      if (atom^[i].element = 'S ') then       // v0.3h
        begin
          if (total_bonds > 2) and (atom^[i].formal_charge < 1) then nval := 4;  // updated in v0.3j
          if total_bonds > 4 then nval := 6;  // this will need some refinement...
        end;                                  // 
      Htotal := nval - total_bonds + atom^[i].formal_charge;
      if (atom^[i].radical_type = 1) or (atom^[i].radical_type = 3) then Htotal := Htotal - 2; // v0.3p
      if (atom^[i].radical_type = 2) then Htotal := Htotal - 1; // v0.3p
      if diazon then Htotal := 0;      // v0.3j
      if Htotal < 0 then Htotal := 0;  // e.g., N in nitro group
      atom^[i].Htot := Htotal;
      if atom^[i].Hexp > atom^[i].Htot then atom^[i].Htot := atom^[i].Hexp;  // v0.3n; just to be sure...
      if is_metal(i) then atom^[i].Htot := atom^[i].Hexp;   // v0.2b  (accept only explicit H on metals)
    end;
end;


procedure update_atypes;
var
  i, j, b_id : integer;
  nb : neighbor_rec;
  single_count, double_count, triple_count, arom_count, acyl_count : integer;
  C_count, O_count : integer;
  total_bonds : integer;
  NdO_count : integer;
  NdC_count : integer;
  Htotal : integer;
begin
  if n_atoms < 1 then exit;
  fillchar(nb,sizeof(neighbor_rec),0);
  for i := 1 to n_atoms do
    begin
      single_count := 0;
      double_count := 0;
      triple_count := 0;
      arom_count   := 0;
      total_bonds  := 0;
      acyl_count   := 0;
      C_count      := 0;
      O_count      := 0;
      NdO_count := 0;
      NdC_count := 0;
      Htotal    := 0;
      nb := get_neighbors(i);
      if atom^[i].neighbor_count > 0 then
        begin  // count single, double, triple, and aromatic bonds to all neighbor atoms
          for j := 1 to atom^[i].neighbor_count do
            begin
              if (is_oxo_C(nb[j])) or (is_thioxo_C(nb[j])) then inc(acyl_count);
              if atom^[(nb[j])].element = 'C ' then inc(C_count);
              if atom^[(nb[j])].element = 'O ' then inc(O_count);
              b_id := get_bond(i,nb[j]);
              if b_id > 0 then
                begin
                  if bond^[b_id].btype = 'S' then inc(single_count);
                  if bond^[b_id].btype = 'D' then inc(double_count);
                  if bond^[b_id].btype = 'T' then inc(triple_count);
                  if bond^[b_id].btype = 'A' then inc(arom_count);
                  if ((atom^[i].element = 'N ') and (atom^[(nb[j])].element = 'O ')) or
                     ((atom^[i].element = 'O ') and (atom^[(nb[j])].element = 'N ')) then
                     begin
                       // check if it is an N-oxide drawn with a double bond ==> should be N3
                       if bond^[b_id].btype = 'D' then inc(NdO_count);
                     end;
                  if ((atom^[i].element = 'N ') and (atom^[(nb[j])].element = 'C ')) or
                     ((atom^[i].element = 'C ') and (atom^[(nb[j])].element = 'N ')) then
                     begin
                       if bond^[b_id].btype = 'D' then inc(NdC_count);
                     end;
                end;
            end;
          total_bonds := single_count + 2*double_count + 3*triple_count + trunc(1.5*arom_count);  
          // calculate number of total hydrogens per atom
          Htotal := nvalences(atom^[i].element) - total_bonds + atom^[i].formal_charge;
          if Htotal < 0 then Htotal := 0;  // e.g., N in nitro group
          atom^[i].Htot := Htotal;
          // refine atom types, based on bond types
          if atom^[i].element = 'C ' then
            begin
              if (arom_count > 1) then atom^[i].atype := 'CAR';
              if (triple_count = 1) or (double_count = 2) then atom^[i].atype := 'C1 ';
              if (double_count = 1) then atom^[i].atype := 'C2 ';
              if (triple_count = 0) and (double_count = 0) and (arom_count < 2) then atom^[i].atype := 'C3 ';
            end;  
          if atom^[i].element = 'O ' then
            begin
              if (double_count = 1) then atom^[i].atype := 'O2 ';
              if (double_count = 0) then atom^[i].atype := 'O3 ';
            end;
          if atom^[i].element = 'N ' then
            begin
              if total_bonds > 3 then
                begin
                  if O_count = 0 then
                    begin
                      if (single_count > 3) or
                        ((single_count = 2) and (double_count = 1) and (C_count >=2)) then
                        atom^[i].formal_charge := 1;
                    end else  // could be an N-oxide -> should be found elsewhere 
                    begin
                      // left empty, so far....
                    end;
                end;
              if (triple_count = 1) or (double_count = 2) then atom^[i].atype := 'N1 ';
              if (double_count = 1) then 
                begin
                  //if NdC_count > 0 then atom^[i].atype := 'N2 ';
                  if (NdC_count = 0) and (NdO_count > 0) and
                     (C_count >= 2) then atom^[i].atype := 'N3 '  // N-oxide is N3 except in hetarene etc.
                  else atom^[i].atype := 'N2 ';                   // fallback
                end;  
              if (arom_count > 1) then atom^[i].atype := 'NAR';
              if (triple_count = 0) and (double_count = 0) then 
                begin
                  if (atom^[i].formal_charge = 0) then 
                    begin
                      if (acyl_count = 0) then atom^[i].atype := 'N3 ';
                      if (acyl_count > 0) then atom^[i].atype := 'NAM';
                    end;  
                  if (atom^[i].formal_charge = 1) then atom^[i].atype := 'N3+';
                end;
            end;  
          if atom^[i].element = 'P ' then
            begin
              if (single_count > 4) then atom^[i].atype := 'P4 ';
              if (single_count <= 4) and (double_count = 0) then atom^[i].atype := 'P3 ';
              if (double_count = 2) then atom^[i].atype := 'P3D';
            end;
          if atom^[i].element = 'S ' then
            begin
              if (double_count = 1) and (single_count = 0) then atom^[i].atype := 'S2 ';
              if (double_count = 0) then atom^[i].atype := 'S3 ';
              if (double_count = 1) and (single_count > 0) then atom^[i].atype := 'SO ';
              if (double_count = 2) and (single_count > 0) then atom^[i].atype := 'SO2';
            end;
          // further atom types should go here
        end;
    end;
end;


procedure chk_arom;
var
  i, j, pi_count, ring_size : integer;
  testring : ringpath_type;
  a_ref, a_prev, a_next : integer;
  b_bk, b_fw, b_exo : integer;
  bt_bk, bt_fw : char;
  ar_bk, ar_fw, ar_exo : boolean;  // new in v0.3
  conj_intr, ko, aromatic : boolean;
  n_db, n_sb, n_ar : integer;
  cumul : boolean;
  exo_mC : integer;
begin
  if n_rings < 1 then exit;
  // first, do a very quick check for benzene, pyridine, etc.
  for i := 1 to n_rings do
    begin
      ring_size := ringprop^[i].size;
      if (ring_size = 6) then
        begin
          fillchar(testring,sizeof(ringpath_type),0);
          for j := 1 to ring_size do testring[j] := ring^[i,j];
          cumul := false;
          n_sb := 0;
          n_db := 0;
          n_ar := 0;
          a_prev := testring[ring_size];
          for j := 1 to ring_size do
            begin
              a_ref := testring[j];
              if (j < ring_size) then a_next := testring[(j+1)] else a_next := testring[1];
              b_bk  := get_bond(a_prev,a_ref);
              b_fw  := get_bond(a_ref,a_next);
              bt_bk := bond^[b_bk].btype;
              bt_fw := bond^[b_fw].btype;
              if (bt_fw = 'S') then inc(n_sb);
              if (bt_fw = 'D') then inc(n_db);
              if (bt_fw = 'A') then inc(n_ar);
              if (bt_fw <> 'A') and (bt_bk = bt_fw) then cumul := true;
              a_prev := a_ref;
            end;
          if (n_ar = 6) or ((n_sb = 3) and (n_db = 3) and (cumul = false)) then
            begin   // this ring is aromatic
              a_prev := testring[ring_size];
              for j := 1 to ring_size do
                begin
                  a_ref := testring[j];
                  b_bk  := get_bond(a_prev,a_ref);
                  bond^[b_bk].arom := true;
                  a_prev := a_ref;
                end;
              ringprop^[i].arom := true;            
            end;
        end;
    end;  
  for i := 1 to n_rings do
    begin
      if (ringprop^[i].arom = false) then   
        begin   // do the hard work only for those rings which are not yet flagged aromatic
          fillchar(testring,sizeof(ringpath_type),0);
          for j := 1 to max_ringsize do if ring^[i,j] > 0 then testring[j] := ring^[i,j];
          ring_size := path_length(testring);
          {$IFDEF debug}
          if (ring_size <> ringprop^[i].size) then
            begin
              debugoutput('Oops! Ring size mismatch in chk_arom');
            end;
          {$ENDIF}
          pi_count  := 0;
          conj_intr := false;
          ko        := false;
          a_prev    := testring[ring_size];
          for j := 1 to ring_size do
            begin
              a_ref := testring[j];
              if (j < ring_size) then a_next := testring[(j+1)] else a_next := testring[1];
              b_bk  := get_bond(a_prev,a_ref);
              b_fw  := get_bond(a_ref,a_next);
              bt_bk := bond^[b_bk].btype;
              bt_fw := bond^[b_fw].btype;
              ar_bk := bond^[b_bk].arom;
              ar_fw := bond^[b_fw].arom;
              if ((bt_bk = 'S') and (bt_fw = 'S') and (ar_bk = false) and (ar_fw = false)) then
                begin
                  // first, assume the worst case (interrupted conjugation)
                  conj_intr := true;  
                  // conjugation can be restored by hetero atoms
                  if (atom^[a_ref].atype = 'O3 ') or (atom^[a_ref].atype = 'S3 ') or
                     (atom^[a_ref].element = 'N ') or (atom^[a_ref].element = 'SE') then
                     begin
                       conj_intr := false;
                       inc(pi_count,2);  // lone pair adds for 2 pi electrons
                     end;
                  // conjugation can be restored by a formal charge at a methylene group
                  if (atom^[a_ref].element = 'C ') and (atom^[a_ref].formal_charge <> 0) then
                    begin
                      conj_intr := false;
                      pi_count  := pi_count - atom^[a_ref].formal_charge;  // neg. charge increases pi_count!
                    end;
                  // conjugation can be restored by carbonyl groups etc.
                  if (is_oxo_C(a_ref)) or (is_thioxo_C(a_ref)) or (is_exocyclic_imino_C(a_ref,i)) then
                    begin
                      conj_intr := false;
                    end;
                  // conjugation can be restored by exocyclic C=C double bond,
                  // adds 2 pi electrons to 5-membered rings, not to 7-membered rings (CAUTION!)
                  // apply only to non-aromatic exocyclic C=C bonds
                  exo_mC := find_exocyclic_methylene_C(a_ref,i);  // v0.3j
                  if ((exo_mC > 0) and odd(ring_size)) then       // v0.3j
                    begin
                      b_exo  := get_bond(a_ref,exo_mC);           // v0.3j 
                      ar_exo := bond^[b_exo].arom;
                      if ((ring_size - 1) mod 4 = 0) then  // 5-membered rings and related
                        begin
                          conj_intr := false;
                          inc(pi_count,2);
                        end else                           // 7-membered rings and related
                        begin
                          if not ar_exo then conj_intr := false;
                        end;
                    end;
                  // if conjugation is still interrupted ==> knock-out
                  if conj_intr then ko := true;
                end else
                begin
                  if ((bt_bk = 'S') and (bt_fw = 'S') and (ar_bk = true) and (ar_fw = true)) then
                    begin
                      if (atom^[a_ref].atype = 'O3 ') or (atom^[a_ref].atype = 'S3 ') or
                         (atom^[a_ref].element = 'N ') or (atom^[a_ref].element = 'SE') then
                         begin
                           inc(pi_count,2);  // lone pair adds for 2 pi electrons
                         end;
                      if (atom^[a_ref].element = 'C ') and (atom^[a_ref].formal_charge <> 0) then
                        begin
                          pi_count  := pi_count - atom^[a_ref].formal_charge;  // neg. charge increases pi_count!
                        end;
                      exo_mC := find_exocyclic_methylene_C(a_ref,i);  // v0.3j
                      if ((exo_mC > 0) and odd(ring_size)) then       // v0.3j
                        begin
                          b_exo := get_bond(a_ref,exo_mC);            // v0.3j
                          ar_exo := bond^[b_exo].arom;
                          if ((ring_size - 1) mod 4 = 0) then  // 5-membered rings and related
                            begin
                              inc(pi_count,2);
                            end;
                        end;
                    end else    // any other case: increase pi count by one electron
                  inc(pi_count);
                end;
              // last command:
              a_prev := a_ref;
            end;  // for j := 1 to ring_size
          // now we can draw our conclusion
          if not ((ko) or (odd(pi_count))) then
            begin  // apply Hueckel's rule
              if (abs(ring_size - pi_count) < 2) and ((pi_count - 2) mod 4 = 0) then
                begin
                  // this ring is aromatic
                  ringprop^[i].arom := true;
                  // now mark _all_ bonds in the ring as aromatic
                  a_prev := testring[ring_size];
                  for j := 1 to ring_size do
                    begin
                      a_ref := testring[j];
                      bond^[get_bond(a_prev,a_ref)].arom := true;
                      a_prev := a_ref;
                     end;
                end;
            end;
    
        end;
    end;  // (for i := 1 to n_rings)
  // finally, mark all involved atoms as aromatic
  for i := 1 to n_bonds do
    begin
      if bond^[i].arom then
        begin
          atom^[(bond^[i].a1)].arom := true;
          atom^[(bond^[i].a2)].arom := true;
        end;
    end;
  // update aromaticity information in ringprop
  for i := 1 to n_rings do
    begin
      testring := ring^[i];
      ring_size := path_length(testring);
      aromatic := true;
      a_prev := testring[ring_size];
      for j := 1 to ring_size do
        begin
          a_ref := testring[j];
          if (not bond^[get_bond(a_prev,a_ref)].arom) then aromatic := false;
          a_prev := a_ref;
        end;
      if aromatic then ringprop^[i].arom := true else ringprop^[i].arom := false;
    end;  
end;


procedure readinputfile(molfilename:string);
var
  rline : string;
begin
  molbufindex := 0;
  if not opt_stdin then
    begin
      if not rfile_is_open then
        begin
          assign(rfile,molfilename);
          reset(rfile);
          rfile_is_open := true;
        end;
      rline := '';
      mol_in_queue := false;
      while (not eof(rfile)) and (pos('$$$$',rline) = 0) do
        begin
          readln(rfile,rline);
          //mol_in_queue := false;
          if molbufindex < (max_atoms+max_bonds+64) then
            begin
              inc(molbufindex);
              molbuf^[molbufindex] := rline;
            end else
            begin
              writeln('Not enough memory for molfile! ',molbufindex);
              close(rfile);
              halt(1);
            end;
          if pos('$$$$',rline) > 0 then mol_in_queue := true;
        end;
      if eof(rfile) then
        begin
          close(rfile);
          rfile_is_open := false;
          mol_in_queue := false;
        end;
    end else              // read from standard input
    begin
      rline := '';
      mol_in_queue := false;
      while (not eof) and (pos('$$$$',rline) = 0) do
        begin
          readln(rline);
          if molbufindex < (max_atoms+max_bonds+64) then
            begin
              inc(molbufindex);
              molbuf^[molbufindex] := rline;
            end else
            begin
              writeln('Not enough memory!');
              halt(1);
            end;
          if pos('$$$$',rline) > 0 then mol_in_queue := true;
        end;
    end;
end;


procedure open_rfile(molfilename:string);
begin
  if not opt_stdin then
    begin
      if not rfile_is_open then
        begin
          assign(rfile,molfilename);
          reset(rfile);
          rfile_is_open := true;
        end;
      if eof(rfile) then
        begin
          close(rfile);
          rfile_is_open := false;
        end;
    end else
    begin
      if not rfile_is_open then
        begin
          assign(rfile,'');
          reset(rfile);
          //rfile_is_open := true;
        end;
      if eof(rfile) then
        begin
          close(rfile);
          rfile_is_open := false;
        end;
    end;
end;


function read_rxnheader:boolean;
var
  rline : string;
  is_OK : boolean;
  n_reactants_str : string;
  n_products_str : string;
begin
  is_OK := true;
  n_reactants_str := '';
  n_products_str := '';
  readln(rfile,rline); inc(ln);
  if (pos('$RDFILE',rline)=1) then  // this is an RDF file
    begin
      while not eof(rfile) and (pos('$RFMT',rline)=0) do 
        begin
          readln(rfile,rline);
          inc(ln);
        end
    end;
  while not eof(rfile) and (pos('$RXN',rline)=0) do
    begin
      readln(rfile,rline); inc(ln);  // this should be "$RXN"
    end;
  if (pos('$RXN',rline)=1) then
    begin
      readln(rfile,rline); inc(ln);
      readln(rfile,rline); inc(ln);
      readln(rfile,rline); inc(ln);
      readln(rfile,rline); inc(ln);
      n_reactants_str := copy(rline,1,3);
      n_products_str := copy(rline,4,3);
      n_reactants := strtoint(n_reactants_str);
      n_products := strtoint(n_products_str);
      //writeln('number of reactants: ',n_reactants,' number of products: ',n_products);
    end else is_OK := false;
  read_rxnheader := is_OK;
end;


procedure read_rxnmol;  // v0.2
var
  rline : string;
begin
  rline := '';
  molbufindex := 0;
  while not eof(rfile) and (pos('$MOL',rline)=0) do 
    begin
      readln(rfile,rline);
      inc(ln);
    end;
  while not eof(rfile) and (pos('M  END',rline)=0) do
    begin
      readln(rfile,rline); inc(ln);
      if molbufindex < (max_atoms+max_bonds+64) then
        begin
          inc(molbufindex);
          molbuf^[molbufindex] := rline;
        end else
        begin
          writeln('Not enough memory for molfile! ',molbufindex);
          close(rfile);
          halt(1);
        end;
    end;
  li := 1;
  read_MDLmolfile('');
end;


procedure skip_data;
var
  rline : string;
begin
  rline := '';
  while not eof(rfile) and (pos('$RFMT',rline)=0) do 
    begin
      readln(rfile,rline);
      inc(ln);
    end;
end;


procedure clear_rings;
var
  i : integer;
begin
  n_rings := 0;
  fillchar(ring^, sizeof(ringlist),0);
  for i := 1 to max_rings do
    begin
      ringprop^[i].size     := 0;
      ringprop^[i].arom     := false;
      ringprop^[i].envelope := false;
    end;
  if n_atoms > 0 then
    begin
      for i := 1 to n_atoms do atom^[i].ring_count := 0;
    end;
  if n_bonds > 0 then
    begin
      for i := 1 to n_bonds do bond^[i].ring_count := 0;
    end;
end;


function ring_lastpos(s:ringpath_type):integer;
var
  i, rc, rlp : integer;
begin
  rlp := 0;
  if n_rings > 0 then
    begin
      for i := 1 to n_rings do
        begin
          rc := ringcompare(s, ring^[i]);
          if rc_identical(rc) then rlp := i;
        end;
    end;
  ring_lastpos := rlp;
end;


procedure remove_redundant_rings;
var
  i, j, k, rlp : integer;
  tmp_path : ringpath_type;
begin
  if n_rings < 2 then exit;
  for i := 1 to (n_rings - 1) do
    begin
      tmp_path := ring^[i];
      rlp := ring_lastpos(tmp_path);
      while rlp > i do
        begin
          for j := rlp to (n_rings - 1) do
            begin
              ring^[j] := ring^[(j+1)];
              ringprop^[j].size := ringprop^[(j+1)].size;
              ringprop^[j].arom := ringprop^[(j+1)].arom;
              ringprop^[j].envelope := ringprop^[(j+1)].envelope;
            end;
          for k := 1 to max_ringsize do ring^[n_rings,k] := 0;
          dec(n_rings);
          rlp := ring_lastpos(tmp_path);
        end;
    end;
end;


function count_aromatic_rings:integer;
var
  i, n : integer;
begin
  n := 0;
    if n_rings > 0 then
      begin
        for i := 1 to n_rings do
          if ringprop^[i].arom then inc(n);
      end;
  count_aromatic_rings := n;
end;


procedure chk_envelopes;
// checks if a ring completely contains one or more other rings
var
  a,i,j,k,l,pl,pli : integer;
  found_atom, found_all_atoms, found_ring : boolean;
begin
  if n_rings < 2 then exit;
  for i := 2 to n_rings do
    begin
      found_ring := false;
      j := 0;
      pli := ringprop^[i].size;
      while ((j < (i-1)) and (found_ring = false)) do
        begin
          inc(j);
          found_all_atoms := true;
          pl := ringprop^[j].size;
          for k := 1 to pl do
            begin
              found_atom := false;
              a := ring^[j,k];
              for l := 1 to pli do
                begin
                  if ring^[i,l] = a then found_atom := true;
                end;
              if found_atom = false then found_all_atoms := false;
            end;
          if found_all_atoms then found_ring := true;
        end;
      if found_ring then ringprop^[i].envelope := true;
    end;
end;


procedure update_ringcount;
var
  i, j, a1, a2, b, pl : integer;
begin
  if n_rings > 0 then
    begin
      chk_envelopes;
      for i := 1 to n_rings do
        begin
          if (ringprop^[i].envelope = false) then
            begin
              pl := ringprop^[i].size;  // path_length(ring^[i]);
              a2 := ring^[i,pl];
              for j := 1 to pl do
                begin
                  a1 := ring^[i,j];
                  inc(atom^[a1].ring_count);
                  b := get_bond(a1,a2);
                  inc(bond^[b].ring_count);
                  a2 := a1;
                end;
            end;
        end;
    end;
end;

//==================================molecule adjustment routines====

procedure scale_mol;
var
  i, a1, a2 : integer;
  a1el, a2el : str2;
  bt : char;
  ar : boolean;
  sum_CCsingle   : double;
  sum_CCdouble   : double;
  sum_CCarom     : double;
  sum_XY         : double;
  n_CCsingle     : integer;
  n_CCdouble     : integer;
  n_CCarom       : integer;
  n_XY           : integer;
  a1p, a2p       : p_3d;
  a1a2dist       : double;
  sf1, sf2, sfa  : double;
begin
  if n_bonds < 1 then exit;
  sum_CCsingle   := 0;
  sum_CCdouble   := 0;
  sum_CCarom     := 0;
  sum_XY         := 0;
  n_CCsingle     := 0;
  n_CCdouble     := 0;
  n_CCarom       := 0;
  n_XY           := 0;
  for i := 1 to n_bonds do
    begin
      ar := bond^[i].arom;
      bt := bond^[i].btype;
      a1 := bond^[i].a1;
      a2 := bond^[i].a2;    
      a1el := atom^[a1].element;
      a2el := atom^[a2].element;
      a1p.x := atom^[a1].x;
      a1p.y := atom^[a1].y;
      a1p.z := atom^[a1].z;
      a2p.x := atom^[a2].x;
      a2p.y := atom^[a2].y;
      a2p.z := atom^[a2].z;
      a1a2dist := dist3d(a1p,a2p);
      sum_XY := sum_XY + a1a2dist;
      inc(n_XY);
      if ((a1el = 'C ') and (a2el = 'C ')) then
        begin
          if (not ar) then
            begin
              if ((bt = 'S')) then
                begin
                  inc(n_CCsingle);
                  sum_CCsingle := sum_CCsingle + a1a2dist;
                end;
              if ((bt = 'D')) then
                begin
                  inc(n_CCdouble);
                  sum_CCdouble := sum_CCdouble + a1a2dist;
                end;
            end else
            begin
              inc(n_CCarom);
              sum_CCarom := sum_CCarom + a1a2dist;
            end;
        end;
    end;
  sf1 := 1; sf2 := 1; sfa := 1; sf_mol := 1;
  if (n_CCsingle > 0) then sf1 := std_blCCsingle / (sum_CCsingle / n_CCsingle);
  if (n_CCdouble > 0) then sf2 := std_blCCdouble / (sum_CCdouble / n_CCdouble);
  if (n_CCarom   > 0) then sfa := std_blCCarom   / (sum_CCarom   / n_CCarom);
  if (n_CCsingle > 0) then
    begin
      if (n_CCdouble > 0) then
        begin
          if (n_CCarom > 0) then sf_mol := ((sf1 + sf2 + sfa) / 3) else sf_mol := ((sf1 + sf2) / 2);
        end else
        begin
          if (n_CCarom > 0) then sf_mol := ((sf1 + sfa) / 2) else sf_mol := sf1;
        end;
    end else
    begin
      if (n_CCdouble > 0) then
        begin
          if (n_CCarom > 0) then sf_mol := ((sf2 + sfa) / 2) else sf_mol := sf2;
        end else sf_mol := sfa;
    end;
  if ((n_CCsingle + n_CCdouble + n_CCarom) = 0) then
    begin
      sf_mol := std_bondlength / (sum_XY / n_XY);
      //writeln('% emergency scaling: ',sf_mol:1:4);
    end;
  if (sf_mol <> 1) then
    begin
      for i := 1 to n_atoms do
        begin
          atom^[i].x := atom^[i].x * sf_mol;
          atom^[i].y := atom^[i].y * sf_mol;
          atom^[i].z := atom^[i].z * sf_mol;
        end;
      if n_brackets > 0 then
        begin
          for i := 1 to n_atoms do
            begin
              with bracket^[i] do
                begin
                  x1 := x1 * sf_mol;
                  y1 := y1 * sf_mol;
                  x2 := x2 * sf_mol;
                  y2 := y2 * sf_mol;
                  x3 := x3 * sf_mol;
                  y3 := y3 * sf_mol;
                  x4 := x4 * sf_mol;
                  y4 := y4 * sf_mol;
                end;            
            end;
        end;
      if n_sgroups > 0 then
        begin
          for i := 1 to n_sgroups do
            begin
              with sgroup^[i] do
                begin
                  x := x * sf_mol;
                  y := y * sf_mol;
                end;
            end;
        end; 

    end;
  //if (n_CCsingle > 0 ) then writeln('% avg. C-C single bond length: ',(sum_CCsingle / n_CCsingle):1:4);
  //if (n_CCdouble > 0 ) then writeln('% avg. C=C double bond length: ',(sum_CCdouble / n_CCdouble):1:4);
  //if (n_CCarom   > 0 ) then writeln('% avg. C=C arom.  bond length: ',(sum_CCarom / n_CCarom):1:4);
  //writeln('% molecule scaled by ',sf_mol:1:5);
end;


procedure center_mol;
var
  i : integer;
  xmin, xmax, ymin, ymax, zmin, zmax : single;
  xcenter, ycenter, zcenter, xcurr : single;
  halfheight : single;
  al, lstr : string;
  ap : integer;
  just : char;
  lw : double;
begin
  if n_atoms = 0 then exit;
  xmax := -1000; xmin := 1000;
  ymax := -1000; ymin := 1000;
  zmax := -1000; zmin := 1000;
  for i := 1 to n_atoms do
    begin
      if atom^[i].x > xmax then xmax := atom^[i].x;
      if atom^[i].x < xmin then xmin := atom^[i].x;    
      if atom^[i].y > ymax then ymax := atom^[i].y;
      if atom^[i].y < ymin then ymin := atom^[i].y;    
      if atom^[i].z > zmax then zmax := atom^[i].z;
      if atom^[i].z < zmin then zmin := atom^[i].z;   

      // check also for left parts of alias labels (the right parts
      // will be checked elsewhere)   v0.4
      if atom^[i].alias <> '' then
        begin
          xcurr := atom^[i].x;
          al := atom^[i].alias;
          case atom^[i].a_just of
            0 : just := 'L';
            1 : just := 'R';
            2 : just := 'C';
          end;
          while (pos('\S',al)>0) do delete(al,pos('\S',al),2);
          while (pos('\s',al)>0) do delete(al,pos('\s',al),2);
          while (pos('\n',al)>0) do delete(al,pos('\n',al),2);
          ap := pos('^',al);
          if (ap = 0) then
            begin
              if (just = 'R') then ap := length(al)-1;
              if (just = 'C') then ap := length(al) div 2;
            end;
          if (ap > 1) then
            begin
              lstr := copy(al,1,(ap-1));
              lw := 0.0375*get_stringwidth(fontsize1,lstr);
              if (xcurr - lw) < xmin then xmin := (xcurr - lw);    
            end;
        end;
    end;
  xcenter := (xmax + xmin) / 2;
  ycenter := (ymax + ymin) / 2;
  zcenter := (zmax + zmin) / 2;
  if rxn_mode then   // v0.2
    begin
      //xcenter := 0;   // removed in v0.4b
      halfheight := (ymax - ymin) / 2;
      if ((ycenter - (ycenter - 1.05*halfheight)) > (yoffset - y_margin)) then
        begin
          ycenter := ycenter - (1.05*halfheight - (yoffset - y_margin));
        end;
    end;
  for i := 1 to n_atoms do
    begin
      atom^[i].x := atom^[i].x - xcenter;  // v0.2
      atom^[i].y := atom^[i].y - ycenter;
      atom^[i].z := atom^[i].z - zcenter;
    end;
  if n_brackets > 0 then  // v0.1f
    begin
      for i := 1 to n_brackets do
        begin
          with bracket^[i] do
            begin
              x1 := x1 - xcenter;
              y1 := y1 - ycenter;
              x2 := x2 - xcenter;
              y2 := y2 - ycenter;
              x3 := x3 - xcenter;
              y3 := y3 - ycenter;
              x4 := x4 - xcenter;
              y4 := y4 - ycenter;
            end;
        end;
    end;
  if n_sgroups > 0 then  // v0.1f
    begin
      for i := 1 to n_sgroups do
        begin
          with sgroup^[i] do
            begin
              x := x - xcenter;
              y := y - ycenter;
            end;
        end;
    end;
  if not rxn_mode then
    begin
      xoffset := abs(xcenter-xmin) + 1.75;  // may require some adjustment
      yoffset := abs(ycenter-ymin) + 1.2;  // may require some adjustment
      maxY := 2 * yoffset;
    end;
end;


function get_pivotscore(r:integer):integer;
var
  j : integer;
  a1, a2, b, rc, nrc, maxrc, rs : integer;
  ar : boolean;
  res : integer;
begin
  res := 0;
  if (n_rings >= r) then
    begin
      rs := ringprop^[r].size;
      ar := ringprop^[r].arom;
      a2 := ring^[r,rs];
      maxrc := 1;
      nrc := 0;
      for j := 1 to rs do
        begin
          a1 := ring^[r,j];
          b := get_bond(a1,a2);
          rc := bond^[b].ring_count;
          if rc > maxrc then maxrc := rc;
          if rc > 1 then inc(nrc);
          a2 := a1;  
        end;
      if ar then res := res + 1000;
      res := res + maxrc * 10 + nrc * 100;
      res := res + (max_ringsize - (6 - rs));
    end;
  get_pivotscore := res;
end;


function find_pivotring:integer;
var
  i : integer;
  p, pscore : integer;
  res : integer;
begin
  res := 0;
  pscore := 0;
  if (n_rings > 0) then
    begin
      for i := 1 to n_rings do
        begin
          if (not ringprop^[i].envelope) then
            begin
              p := get_pivotscore(i);
              //writeln('% pivotscore for ring ',i,': ',p);
              if p > pscore then 
                begin
                  pscore := p;
                  res := i;
                end;
            end;  
        end;
    end;
  find_pivotring := res;
end;


procedure rotxz(theta:double);
var
  i : integer;
  cost, sint : double;
  ax, az : double;
begin
  cost := cos(theta);
  sint := sin(theta);
  for i := 1 to n_atoms do
    begin
      ax := atom^[i].x;
      az := atom^[i].z;
      atom^[i].x := cost*ax - sint*az;
      atom^[i].z := cost*az + sint*ax;
    end;
end;


procedure rotxy(theta:double);
var
  i : integer;
  cost, sint : double;
  ax, ay : double;
begin
  cost := cos(theta);
  sint := sin(theta);
  for i := 1 to n_atoms do
    begin
      ax := atom^[i].x;
      ay := atom^[i].y;
      atom^[i].x := cost*ax - sint*ay;
      atom^[i].y := cost*ay + sint*ax;
    end;
end;


procedure rotzy(theta:double);
var
  i : integer;
  cost, sint : double;
  az, ay : double;
begin
  cost := cos(theta);
  sint := sin(theta);
  for i := 1 to n_atoms do
    begin
      az := atom^[i].z;
      ay := atom^[i].y;
      atom^[i].z := cost*az - sint*ay;
      atom^[i].y := cost*ay + sint*az;
    end;
end;


function find_pivotbond(r:integer):integer;
var
  j : integer;
  a1, a2, b, rc, maxrc, rs : integer;
  res : integer;
begin
  res := 0;
  if (n_rings >= r) then
    begin
      rs := ringprop^[r].size;
      a2 := ring^[r,rs];
      maxrc := 1;
      for j := 1 to rs do
        begin
          a1 := ring^[r,j];
          b := get_bond(a1,a2);
          rc := bond^[b].ring_count;
          if rc > maxrc then 
            begin
              maxrc := rc;
              res := b;
            end;  
          a2 := a1;  
        end;
    end;
  find_pivotbond := res;
end;


procedure rotate_mol;
var
  pr, pb : integer;
  acorner, a1, a2 : integer;
  rs, a1pos, a2pos : integer;
  acp, a1p, a2p, pp, refp, viewp : p_3d;
  ppnorm, origin, dummy : p_3d;
  xyangle, xzangle, yzangle : double;
begin
  if n_brackets > 0 then exit;  // avoid any rotation when brackets are present;  v0.1f
  if (n_sgroups > 0) and (opt_sgroups = true) then exit;  // v0.2a
  if (n_rings < 1) then
    begin
      // whatever...
    end else
    begin
      pr := find_pivotring;
      if (pr > 0) and (pr <= n_rings) then
        begin
          rs := ringprop^[pr].size;
          a1pos := 1 + round(rs/3);
          a2pos := (rs + 1) - round(rs/3);  
          acorner := ring^[pr,1];
          a1 := ring^[pr,a1pos];
          a2 := ring^[pr,a2pos];
          origin.x := 0; origin.y := 0; origin.z := 0;
          acp.x := atom^[acorner].x; acp.y := atom^[acorner].y; acp.z := atom^[acorner].z;
          a1p.x := atom^[a1].x; a1p.y := atom^[a1].y; a1p.z := atom^[a1].z;
          a2p.x := atom^[a2].x; a2p.y := atom^[a2].y; a2p.z := atom^[a2].z;
          pp := cross_prod(acp,a1p,a2p);   // normal vector
          if (pp.z < acp.z) then pp := cross_prod(acp,a2p,a1p);
          ppnorm := pp;
          dummy := acp;
          vec2origin(dummy,ppnorm);
          //====now get the angles
          // XZ (rotate around Y axis
          refp.x := 0; refp.y := 0; refp.z := 2;
          viewp.x := 0; viewp.y := 2; viewp.z := 0;
          xzangle := ctorsion(viewp,origin,refp,ppnorm);
          // YZ (rotate around X axis
          viewp.x := 2; viewp.y := 0; viewp.z := 0;
          yzangle := ctorsion(viewp,origin,refp,ppnorm);
          if (abs(xzangle) > 0) then
            begin
              rotxz(xzangle);
              yrot := xzangle;
            end;
          if (abs(yzangle) > 0) then
            begin
              rotzy(yzangle);
              xrot := yzangle;
            end;
          pb := 0;  
          pb := find_pivotbond(pr);
          if (pb > 0) then
            begin
              a1 := bond^[pb].a1;
              a2 := bond^[pb].a2;              
              a1p.x := atom^[a1].x; a1p.y := atom^[a1].y; a1p.z := atom^[a1].z;
              a2p.x := atom^[a2].x; a2p.y := atom^[a2].y; a2p.z := atom^[a2].z;
              refp.x := a1p.x; refp.y := (a1p.y + 2); refp.z := a1p.z;
              viewp.x := a1p.x; viewp.y := a1p.y; viewp.z := (a1p.z + 2);
              if (a2p.y < a1p.y) then refp.y := (a1p.y - 2);
              xyangle := ctorsion(viewp,a1p,refp,a2p);
               if (abs(xyangle) > 0) then
                begin
                  rotxy(-xyangle);
                  zrot := xyangle;
                end;
            end;  
        end;
    end;
end;


function is_3dfile:boolean;
var
  i : integer;
  res : boolean;
begin
  res := false;
  if (n_atoms > 0) then
    begin
      for i := 1 to n_atoms do if (atom^[i].z <> 0) then res := true;
    end;
  is_3dfile := res;
end;


procedure adjust_mol;
begin
  if opt_autoscale then scale_mol;
  if opt_autorotate or (opt_autorotate3Donly and is_3dfile) then rotate_mol else
    begin
      if (xrot <> 0) then rotzy(xrot);
      if (yrot <> 0) then rotxz(yrot);
      if (zrot <> 0) then rotxy(zrot);
    end;
  center_mol;
end;


function in_ring(r,a1,a2,a3:integer):boolean;
var
  j : integer;
  res : boolean;
  complete : boolean;
  missing : boolean;
begin
  res := false;
  if n_rings >= r then
    begin
      complete := true;
      missing := true;
      for j := 1 to max_ringsize do
        begin
          if ring^[r,j] = a1 then missing := false;
        end;
      if missing then complete := false;
      missing := true;
      for j := 1 to max_ringsize do
        begin
          if ring^[r,j] = a2 then missing := false;
        end;
      if missing then complete := false;
      missing := true;
      for j := 1 to max_ringsize do
        begin
          if ring^[r,j] = a3 then missing := false;
        end;
      if missing then complete := false;
      if complete then res := true;  
    end;
  in_ring := res;
end;


function is_kekulering(r:integer):boolean;
var
  i : integer;
  res : boolean;
  a1, a2 : integer;
  bt : char;
  b, rs : integer;
  ns, nd, na : integer;
begin
  res := false;
  if n_rings >= r then 
    begin
      rs := ringprop^[r].size;
      a1 := ring^[r,rs];
      ns := 0; nd := 0; na := 0;
      for i := 1 to rs do
        begin
          a2 := ring^[r,i];
          b := get_bond(a1,a2);
          bt := bond^[b].btype;
          if bt = 'D' then inc(nd);
          if bt = 'S' then inc(ns);
          if bt = 'A' then inc(na);
          a1 := a2;
        end;
      if na = rs then res := true;
      if (ns > 0) and (nd > 0) and (ns = nd) then res := true;
    end;
  if not ringprop^[r].arom then res := false;
  is_kekulering := res;
end;


function get_ringscore(a1,a2,a3:integer):integer;
var
  i : integer;
  res : integer;
  atoms_in_ring : boolean;
  atoms_in_aromring : boolean;
  atoms_in_kekulering : boolean;
  rsize : integer;
begin
  res := 0;
  if (n_rings > 0) then
    begin
      atoms_in_ring := false;
      atoms_in_aromring := false;
      atoms_in_kekulering := false;
      rsize := max_ringsize;
      for i := 1 to n_rings do
        begin
          if in_ring(i,a1,a2,a3) then 
            begin
              atoms_in_ring := true;
              if ringprop^[i].arom then atoms_in_aromring := true;
              if ringprop^[i].size < rsize then rsize := ringprop^[i].size;
              if is_kekulering(i) then atoms_in_kekulering := true;
            end;  
        end;
      if atoms_in_ring then res := res + 10;  
      if atoms_in_aromring then res := res + 1000;  
      if atoms_in_kekulering then res := res + 1;  
      case rsize of
        6 : res := res + 100;
        5 : res := res + 90;
        7 : res := res + 80;
        8 : res := res + 70;
        9 : res := res + 60;
       10 : res := res + 50;
      end;
    end;
  get_ringscore := res;
end;


procedure refine_bonds;
var
  i, j : integer;
  ba1, ba2 : integer;
  nb1, nb2 : neighbor_rec;
  nb_bond : integer;
  cand1, cand2 : integer;
  rs1, rs2 : integer;
begin
  if n_bonds < 1 then exit;
  for i := 1 to n_bonds do
    begin
      if ((bond^[i].btype = 'D') or (bond^[i].btype = 'A')) then
        begin
          ba1 := bond^[i].a1;
          ba2 := bond^[i].a2;
          if (atom^[ba1].neighbor_count = 1) and (atom^[ba2].neighbor_count > 1) then
            begin
              nb2 := get_nextneighbors(ba2,ba1);
              bond^[i].bsubtype := 'A';
              bond^[i].a_handle := nb2[1];
              if (atom^[ba2].neighbor_count >= 2) then
                begin
                  for j := 1 to atom^[ba2].neighbor_count do
                    begin
                      nb_bond := get_bond(ba2,nb2[j]);
                      if (bond^[nb_bond].btype = 'D') then    //  ==> allene, sulfate etc.!
                        begin
                          bond^[i].bsubtype := 'N';
                          bond^[i].a_handle := 0;
                        end;
                      end;  
                end; 
              if (atom^[ba2].neighbor_count >= 3) then
                begin
                  bond^[i].bsubtype := 'N';
                end;
            end;
          if (atom^[ba1].neighbor_count > 1) and (atom^[ba2].neighbor_count = 1) then
            begin
              nb1 := get_nextneighbors(ba1,ba2);
              bond^[i].bsubtype := 'A';
              bond^[i].a_handle := nb1[1];
              if (atom^[ba1].neighbor_count >= 2) then
                begin
                  for j := 1 to atom^[ba1].neighbor_count do
                    begin
                      nb_bond := get_bond(ba1,nb1[j]);
                      if (bond^[nb_bond].btype = 'D') then    //  ==> allene, sulfate etc.!
                        begin
                          bond^[i].bsubtype := 'N';
                          bond^[i].a_handle := 0;
                        end;
                      end;  
                end; 
              if (atom^[ba1].neighbor_count >= 3) then
                begin
                  bond^[i].bsubtype := 'N';
                end;
            end;
          if (atom^[ba1].neighbor_count = 2) and (atom^[ba2].neighbor_count = 2) then
            begin
              nb1 := get_nextneighbors(ba1,ba2);
              nb2 := get_nextneighbors(ba2,ba1);
              bond^[i].bsubtype := 'A';
              cand1 := nb1[1];
              cand2 := nb2[1];
              if is_heavyatom(cand1) then bond^[i].a_handle := cand1 else
              bond^[i].a_handle := cand2;  // some more refinement still missing....
            end;
          if (atom^[ba1].neighbor_count = 2) and (atom^[ba2].neighbor_count = 3) then
            begin
              nb1 := get_nextneighbors(ba1,ba2);
              bond^[i].bsubtype := 'A';
              bond^[i].a_handle := nb1[1];
            end;
          if (atom^[ba1].neighbor_count = 3) and (atom^[ba2].neighbor_count = 2) then
            begin
              nb2 := get_nextneighbors(ba2,ba1);
              bond^[i].bsubtype := 'A';
              bond^[i].a_handle := nb2[1];
            end;
          if (atom^[ba1].neighbor_count = 3) and (atom^[ba2].neighbor_count = 3) then
            begin
              nb1 := get_nextneighbors(ba1,ba2);
              cand1 := nb1[1];
              cand2 := nb1[2];
              rs1 := get_ringscore(cand1,ba1,ba2);
              rs2 := get_ringscore(cand2,ba1,ba2);
              bond^[i].a_handle := cand1;   // default
              if (rs1 <> rs2) then 
                begin
                  bond^[i].bsubtype := 'A';
                  if rs1 > rs2 then bond^[i].a_handle := cand1 else
                    bond^[i].a_handle := cand2;
                end else
                begin
                  nb2 := get_nextneighbors(ba2,ba1);
                  cand1 := nb2[1];
                  cand2 := nb2[2];
                  rs1 := get_ringscore(cand1,ba1,ba2);
                  rs2 := get_ringscore(cand2,ba1,ba2);
                  bond^[i].a_handle := cand1;   // default
                  if (rs1 <> rs2) then 
                    begin
                      bond^[i].bsubtype := 'A';
                      if rs1 > rs2 then bond^[i].a_handle := cand1 else
                        bond^[i].a_handle := cand2;
                    end else
                    begin                
                      if (rs1 > 0) then 
                        begin
                          bond^[i].bsubtype := 'A';
                          bond^[i].a_handle := cand1;
                        end;
                    end;    
                end; 
            end;
          if (opt_stripH = false) then    // v0.1f
            begin
              if ((atom^[ba1].neighbor_count + atom^[ba1].Hexp = 1) or
                (atom^[ba1].neighbor_count + atom^[ba1].Hexp >= 3)) and 
                ((atom^[ba2].neighbor_count + atom^[ba2].Hexp = 1) or
                (atom^[ba2].neighbor_count + atom^[ba2].Hexp >= 3)) then
                begin
                  if (bond^[i].ring_count = 0) then bond^[i].bsubtype := 'N';
                end;
            end;
        end;  // end check of double bonds
      if ((bond^[i].btype = 'S') and (bond^[i].stereo = bstereo_up)) then
          bond^[i].bsubtype := 'W';
      if ((bond^[i].btype = 'S') and (bond^[i].stereo = bstereo_down)) then
          bond^[i].bsubtype := 'H';
    end;
  for i := 1 to n_bonds do  // 2nd run
    begin
      if ((bond^[i].btype = 'D') and (bond^[i].a_handle = 0)) then
        begin
          ba1 := bond^[i].a1;
          ba2 := bond^[i].a2;
          if ((atom^[ba1].neighbor_count = 1) and (atom^[ba2].neighbor_count = 2)) then
            begin
              nb2 := get_nextneighbors(ba2,ba1);            
              nb_bond := get_bond(ba2,nb2[1]);
              if (bond^[nb_bond].btype = 'D') then    //  ==> allene etc.!
                begin
                  if (bond^[nb_bond].a_handle > 0) then bond^[i].a_handle := bond^[nb_bond].a_handle;
                  bond^[i].bsubtype := bond^[nb_bond].bsubtype;
                end;
            end; 
          if ((atom^[ba1].neighbor_count = 2) and (atom^[ba2].neighbor_count = 1)) then
            begin
              nb1 := get_nextneighbors(ba1,ba2);            
              nb_bond := get_bond(ba1,nb1[1]);
              if (bond^[nb_bond].btype = 'D') then    //  ==> allene etc.!
                begin
                  if (bond^[nb_bond].a_handle > 0) then bond^[i].a_handle := bond^[nb_bond].a_handle;
                  bond^[i].bsubtype := bond^[nb_bond].bsubtype;
                end;
            end; 
        end;
    end;
end;


procedure chk_hidden;
var
  i, j : integer;
  a1, a2, b : integer;
  el1, el2 : str2;
  nb : neighbor_rec;
  n_db : integer;
begin
  if n_atoms > 0 then
    begin
      for i := 1 to n_atoms do
        begin
          el1   := atom^[i].element;
          nb    := get_neighbors(i);
          a1    := i;
          n_db  := 0;
          for j := 1 to atom^[i].neighbor_count do
            begin
              a2 := nb[j];
              b := get_bond(a1,a2);
              if (bond^[b].btype = 'D') then inc(n_db);
            end;
          if (atom^[i].alias <> '') then atom^[i].hidden := false;  // v0.2b
          if (el1 = 'C ') then atom^[i].hidden := true else atom^[i].hidden := false;
          if (el1 = 'C ') and (atom^[i].neighbor_count = 2) and (n_db = 2) then atom^[i].hidden := false;
          if (el1 = 'H ') and opt_stripH then atom^[i].hidden := true;
          if (opt_Honmethyl and is_methylC(i) and opt_stripH) then atom^[i].hidden := false;
          //if atom^[i].formal_charge <> 0 then atom^[i].hidden := false;  // v0.1d
          if atom^[i].nucleon_number > 0 then atom^[i].hidden := false;
          atom^[i].tag := false;   // reset atom tags;  v0.1f
        end;
    end;
  if n_bonds > 0 then
    begin
      for i := 1 to n_bonds do
        begin
          a1 := bond^[i].a1;
          a2 := bond^[i].a2;
          el1 := atom^[a1].element;
          el2 := atom^[a2].element;
          bond^[i].hidden := false;
          if (el1 = 'H ') or (el2 = 'H ') then
            begin
              if opt_stripH then bond^[i].hidden := true;
              if (el1 = 'H ') and (el2 = 'H ') then bond^[i].hidden := false;
              if opt_Honstereo then
                begin
                  if (bond^[i].stereo = bstereo_up) or (bond^[i].stereo = bstereo_down) then
                    begin
                      bond^[i].hidden := false;
                      if (el1 = 'H ') then 
                        begin
                          atom^[a1].hidden := false;
                          if is_methylC(a2) then atom^[a2].hidden := true;
                        end;
                      if (el2 = 'H ') then 
                        begin
                          atom^[a2].hidden := false;
                          if is_methylC(a1) then atom^[a1].hidden := true;
                        end;
                    end;
                end;
              if (el1 = 'H ') and (atom^[a1].nucleon_number > 0) then
                begin
                  bond^[i].hidden := false;
                  atom^[a2].tag := true;   // v0.1f; mark Deuterium or Tritium-bearing atoms
                end;
              if (el2 = 'H ') and (atom^[a2].nucleon_number > 0) then
                begin
                  bond^[i].hidden := false;
                  atom^[a1].tag := true;   // v0.1f
                end;
            end;           
        end;
    end;
end;

//==============================Postscript and SVG output routines======

procedure printBBdef;
begin
  writeln('/bb {');
  //writeln('1.0 setgray');
  writeln(bgrgbstr,' setrgbcolor');
  writeln('CFont');
  writeln('newpath X dot Y dot moveto');
  writeln('anchor stringwidth pop 2 div ',lblmargin:1:1,' add neg 0 rmoveto');
  writeln('0 fs1 2.5 div ',lblmargin:1:1,' add neg rmoveto');    
  writeln('0 fs1 2.5 div 2 mul ',2*lblmargin:1:1,' add rlineto');               
  writeln('anchor stringwidth pop ',2*lblmargin:1:1,' add 0 rlineto');             
  writeln('0 fs1 2.5 div 2 mul ',2*lblmargin:1:1,' add neg rlineto');           
  writeln('closepath fill');         
  //writeln('0.0 setgray');
  writeln('0 0 0 setrgbcolor');
  writeln('X dot Y dot moveto');                                 
  writeln('} bind def');
  writeln;
end;


procedure printBBXdef;
begin
  writeln('/bbx {');
  //writeln('1.0 setgray');
  writeln(bgrgbstr,' setrgbcolor');
  writeln('CFont');
  writeln('newpath X dot Y dot moveto');
  writeln('anchor stringwidth pop 2 div ',lblmargin:1:1,' add neg 0 rmoveto');
  writeln('0 fs1 2.5 div neg rmoveto');         // bottom left
  writeln('0 fs1 2.5 div 2 mul rlineto');       // top left        
  writeln('anchor stringwidth pop ',2*lblmargin:1:1,' add 0 rlineto');  // top right, big box
  // extra box for "+" or "-" sign
  writeln('0 fs1 6 div rlineto');               // top/top left        
  writeln('fs1 2 div 0 rlineto');               // top/top right
  writeln('0 fs1 2 div neg rlineto');           // top/bottom right
  writeln('fs1 2 div neg 0 rlineto');           // compensates for the extra upshift
  writeln('0 fs1 2 div neg rlineto');           // back on track        
  writeln('closepath fill');         
  //writeln('0.0 setgray');
  writeln('0 0 0 setrgbcolor');
  writeln('X dot Y dot moveto');                                 
  writeln('} bind def');
  writeln;
end;


procedure write_PS_init;
begin
  bgrgbstr := format('%1.2f %1.2f %1.2f',[(bgcolor.r/255),(bgcolor.g/255),(bgcolor.b/255)],fsettings);
  calc_PSboundingbox;
  if opt_eps then 
    begin
      writeln('%!PS-Adobe-3.0 EPSF-3.0');
      write_PSboundingbox;
    end else writeln('%!PS-Adobe-2.0');
  writeln('%%Creator: mol2ps ',version,',  Norbert Haider, University of Vienna, 2014');
  if not opt_stdin then 
    writeln('%%Title: ',molfilename) else 
    writeln('%%Title: reading from standard input');
  writeln('% the following settings were used:');
  writeln('% font: ',fontname,' ',fontsize1,' pt (',fontsize2,' pt for subscripts)');
  writeln('% line width: ',linewidth:1:1);
  if opt_autorotate or (opt_autorotate3Donly and is_3dfile) then
    begin
      writeln('% automatic rotation: ');
      writeln('%      ',radtodeg(xrot):1:2,'° around X axis');
      writeln('%      ',radtodeg(yrot):1:2,'° around Y axis');    
      writeln('%      ',radtodeg(zrot):1:2,'° around Z axis');    
    end else
    begin
      if (xrot <> 0) or (yrot <> 0) or (zrot <> 0) then
        begin
          writeln('% user-specified rotation: ');
          if (xrot <> 0) then writeln('%      ',radtodeg(xrot):1:2,'° around X axis');
          if (yrot <> 0) then writeln('%      ',radtodeg(yrot):1:2,'° around Y axis');    
          if (zrot <> 0) then writeln('%      ',radtodeg(zrot):1:2,'° around Z axis');    
        end;
    end;
  write('% automatic scaling: ');
  if opt_autoscale then writeln('on') else writeln('off');
  if (sf_mol <> 1.0) then writeln('% molecule scaled by ',sf_mol:1:5);
  write('% stripping of explicit hydrogens: ');
  if opt_stripH then writeln('on') else writeln('off');
  write('% hydrogen on hetero atoms: ');
  if opt_Honhetero then writeln('on') else writeln('off');
  write('% print molecule name above structure: ');
  if opt_showmolname then writeln('on') else writeln('off');
  if opt_bgcolor then write_PSbg;
  writeln;
  writeln('% for manual (re-)scaling, please edit the following line:');
  writeln('  ',global_scaling:1:2,' ',global_scaling:1:2,' scale');
  writeln;
  writeln('gsave');
  writeln('/dot {.24 mul} def');
  writeln('0 0 0 setrgbcolor');  // v0.4a
  writeln(linewidth:1:1,' setlinewidth');
  writeln('1 setlinecap');
  writeln('1 setlinejoin');
  writeln('/fs1 ',fontsize1,' def');
  writeln('/fs2 ',fontsize2,' def');
  writeln('/fs3 ',round((fontsize1+fontsize2)/2),' def');
  writeln('/CFont { /',fontname,' findfont fs1 scalefont setfont } def');
  writeln('/CFontSub { /',fontname,' findfont fs2 scalefont setfont } def');
  writeln('/CFontChg { /Courier-Bold findfont fs3 scalefont setfont } def');
  if (opt_atomnum or opt_bondnum or opt_maps) then
    begin
      writeln('/fs4 ',round(fontsize1 / 2.5),' def');
      writeln('/CFontNum { /',fontname,' findfont fs4 scalefont setfont } def');
    end;
  writeln('/Minus { (-) show } def');  // with Courier we don't need '--'
  writeln('/Rad1 { (:) show } def');
  //writeln('/Rad2 { (.) show } def');
  writeln('/Rad2 { (\267) show } def');
  writeln('/Rad3 { /Helvetica-Bold findfont fs3 1.4 div scalefont setfont (^^) show } def');
  writeln;
  printBBdef;
  printBBXdef;
end;

procedure write_SVG_init;
var
  ymaxtotal   : double;
  ymintotal   : double;
  ydiff       : double;
  ydiffscaled : double;
  xmaxscaled  : double;
  ymaxscaled  : double;
  yminscaled  : double;
  rgbhex      : string;
begin
  rgbhex := '#' + inttohex(bgcolor.r,2) + inttohex(bgcolor.g,2) + inttohex(bgcolor.b,2);

  ymaxtotal      := svg_max_y + ymargin + max_ytrans;
  ymintotal      := svg_min_y - 25 + max_ytrans;
  ydiff          := (svg_max_y + 25) - (svg_min_y -25);
  ydiffscaled    := ydiff * global_scaling;
  xmaxscaled     := (svg_max_x + 20) * global_scaling;
  ymaxscaled     := ymaxtotal * global_scaling;
  yminscaled     := ymintotal * global_scaling;
  //  $svgline[1] = "<svg width=\"$xmaxscaled\" height=\"$ydiffscaled\" viewbox=\"0 $ymintotal $xmaxval $ydiff\" xmlns=\"http://www.w3.org/2000/svg\">";

  // the width and height values are placeholders; correct values will be determined during
  // plotting and will be appended at the end of the output, so they can be applied to the
  // final SVG file by a wrapper script
  writeln('<?xml version="1.0" standalone="no" ?>');
  writeln('<svg width="',xmaxscaled:1:0,'" height="',ydiffscaled:1:0,'" viewbox="0 ',ymintotal:1:0,' ',(svg_max_x + 20):1:0,' ',ydiff:1:0,'" xmlns="http://www.w3.org/2000/svg">');
  writeln('<style type="text/css"><![CDATA[ circle { stroke: ',rgbhex,'; fill: ',rgbhex,'; }');
  writeln('text { font-family: ',fontname,'; font-size: ',fontsize1,'px; } line { stroke: #000000; stroke-width: ',linewidth:1:1,'; } ]]> </style>');
  writeln('<g>');
  writeln;
  if opt_bgcolor then
    begin
      writeln('<rect x="0" y="',ymintotal:1:0,'" width="',(svg_max_x + 20):1:0,'" height="',ydiff:1:0,'" style="fill: ',rgbhex,'; stroke-width:0"/>');
      writeln;
    end;
  if opt_showmolname and (molname <> '') then
    begin
      writeln(format('<text style="font-size: %dpx" x="5" y="%1.1f">%s</text>',[fontsize2,(ymintotal+fontsize2),molname],fsettings));
    end;
end;

procedure chk_svg_max_xy(svg_x, svg_y : single);  // v0.2c
// update the global variables svg_max_x, svg_ax_y and svg_min_y by comparison with the actual values
begin
  if (svg_x > svg_max_x) then svg_max_x := svg_x;
  if (svg_y > svg_max_y) then svg_max_y := svg_y;
  if (svg_y < svg_min_y) then svg_min_y := svg_y;
end;


procedure printPSsingle(X1,Y1,X2,Y2 : single);
var
  outXint, outYint, outXint2, outYint2 : integer;
begin
  outXint := round((X1+xoffset)*blfactor);
  outYint := round((Y1+yoffset)*blfactor);
  updatebb(outXint, outYint);
  outXint2 := round((X2+xoffset)*blfactor);
  outYint2 := round((Y2+yoffset)*blfactor);
  updatebb(outXint2, outYint2);
  writeout('%d dot %d dot moveto %d dot %d dot lineto', [outXint, outYint, outXint2, outYint2]);  
end;

procedure printSVGsingle(X1,Y1,X2,Y2 : single);
var
  outX, outY : single;
  bstr : string;  // v0.4
begin
  outX := (X1+xoffset)*blfactor*svg_factor;
  outY := (Y1+yoffset)*blfactor*-svg_factor + svg_yoffset;
  chk_svg_max_xy(outX,outY);
  if (svg_mode = 1) then
    bstr := format('<line x1="%1.1f" y1="%1.1f" ',[outX,outY],fsettings);
  if (svg_mode = 2) then
    bstr := format('M %1.1f %1.1f ',[outX,outY],fsettings);
  outX := (X2+xoffset)*blfactor*svg_factor;
  outY := (Y2+yoffset)*blfactor*-svg_factor + svg_yoffset;
  chk_svg_max_xy(outX,outY);
  if (svg_mode = 1) then bstr := bstr + format('x2="%1.1f" y2="%1.1f" />',[outX,outY],fsettings);
  if (svg_mode = 2) then bstr := bstr + format('L %1.1f %1.1f ',[outX,outY],fsettings); 
  writeouts(bstr);
end;

procedure printPSdouble(X1,Y1,X2,Y2 : single);
var
  origX1, origY1, origX2, origY2 : single;
  r, deltaX, deltaY : double;
begin
  origX1 := X1;
  origY1 := Y1;
  origX2 := X2;
  origY2 := Y2;
  r := sqrt(sqr(origX1-origX2)+sqr(origY1-origY2));
  if (r = 0) then   // added check in v0.1f
    begin
      //nothing to draw, atoms are superimposed
      {$IFDEF debug}
      debugoutput('atoms have identical XY coordinates, skipping bond');
      {$ENDIF}
      exit;
    end;
  deltaX := ((PX-1)/2)*(origY2-origY1)/r;
  deltaY := ((PX-1)/2)*(origX2-origX1)/r;
  X1 := origX1-deltaX;
  Y1 := origY1+deltaY;
  X2 := origX2-deltaX;
  Y2 := origY2+deltaY;
  printPSsingle(X1,Y1,X2,Y2);
  X1 := origX1+deltaX;
  Y1 := origY1-deltaY;
  X2 := origX2+deltaX;
  Y2 := origY2-deltaY;
  printPSsingle(X1,Y1,X2,Y2);
end;

procedure printSVGdouble(X1,Y1,X2,Y2 : single);
var
  origX1, origY1, origX2, origY2 : single;
  r, deltaX, deltaY : double;
begin
  origX1 := X1;
  origY1 := Y1;
  origX2 := X2;
  origY2 := Y2;
  r := sqrt(sqr(origX1-origX2)+sqr(origY1-origY2));
  if (r = 0) then   // added check in v0.1f
    begin
      //nothing to draw, atoms are superimposed
      {$IFDEF debug}
      debugoutput('atoms have identical XY coordinates, skipping bond');
      {$ENDIF}
      exit;
    end;
  deltaX := ((PX-1)/2)*(origY2-origY1)/r;
  deltaY := ((PX-1)/2)*(origX2-origX1)/r;
  X1 := origX1-deltaX;
  Y1 := origY1+deltaY;
  X2 := origX2-deltaX;
  Y2 := origY2+deltaY;
  printSVGsingle(X1,Y1,X2,Y2);
  X1 := origX1+deltaX;
  Y1 := origY1-deltaY;
  X2 := origX2+deltaX;
  Y2 := origY2-deltaY;
  printSVGsingle(X1,Y1,X2,Y2);
end;

procedure printPStriple(X1,Y1,X2,Y2 : single);
var
  origX1, origY1, origX2, origY2 : single;
  r, deltaX, deltaY : double;
begin
  origX1 := X1;
  origY1 := Y1;
  origX2 := X2;
  origY2 := Y2;
  r := sqrt(sqr(origX1-origX2)+sqr(origY1-origY2));
  if (r = 0) then   // added check in v0.1f
    begin
      //nothing to draw, atoms are superimposed
      {$IFDEF debug}
      debugoutput('atoms have identical XY coordinates, skipping bond');
      {$ENDIF}
      exit;
    end;
  deltaX := (PX-2)*(origY2-origY1)/r;
  deltaY := (PX-2)*(origX2-origX1)/r;
  printPSsingle(X1,Y1,X2,Y2);
  X1 := origX1-deltaX;
  Y1 := origY1+deltaY;
  X2 := origX2-deltaX;
  Y2 := origY2+deltaY;
  printPSsingle(X1,Y1,X2,Y2);
  X1 := origX1+deltaX;
  Y1 := origY1-deltaY;
  X2 := origX2+deltaX;
  Y2 := origY2-deltaY;
  printPSsingle(X1,Y1,X2,Y2);
end;


function new_p3(fixp,dirp:p_3d; dist:double):p_3d;
var
  ini_dist : double;
  scalingfactor : double;
  diffx, diffy, diffz : double;
  resp : p_3d;
begin
  ini_dist := dist3d(fixp,dirp);
  if (ini_dist = 0) then
    begin
      new_p3 := fixp;
      exit;
    end;
  scalingfactor := dist/ini_dist;
  diffx := dirp.x - fixp.x;
  diffy := dirp.y - fixp.y;
  diffz := dirp.z - fixp.z;
  resp.x := fixp.x + diffx*scalingfactor;
  resp.y := fixp.y + diffy*scalingfactor;
  resp.z := fixp.z + diffz*scalingfactor;
  new_p3 := resp;
end;


procedure printPS2DdoubleN(a1p,a2p:p_3d);
var
  tmp1, tmp2, test1, test2, a_dir, diffp, ahp : p_3d;
  spacing : double;
  diffx, diffy : double;
begin
  {$IFDEF debug}
  debugoutput('entering printPS2DdoubleN');
  {$ENDIF}
  tmp1.x := (a1p.x + a2p.x) / 2;
  tmp1.y := (a1p.y + a2p.y) / 2;
  tmp1.z := (a1p.z + a2p.z) / 2;
  diffx := tmp1.x - a1p.x;
  diffy := tmp1.y - a1p.y;
  ahp.x := tmp1.x - diffy;  // fixed in v0.1c
  ahp.y := tmp1.y + diffx;
  ahp.z := tmp1.z;
  tmp1  := cross_prod(a1p,a2p,ahp);
  test1 := cross_prod(a1p,a2p,tmp1);
  test2 := cross_prod(a1p,tmp1,a2p);
  if (dist3d(ahp,test1) < dist3d(ahp,test1)) then 
    a_dir := test1 else a_dir := test2;
  spacing := std_bondlength * db_spacingfactor;
  tmp1 := new_p3(a1p,a_dir,0.5*spacing);
  diffp := subtract_3d(tmp1,a1p);
  tmp2 := add_3d(a2p,diffp);
  printPSsingle(tmp1.x,tmp1.y,tmp2.x,tmp2.y);
  tmp1  := subtract_3d(a1p,diffp);
  tmp2  := subtract_3d(a2p,diffp);
  printPSsingle(tmp1.x,tmp1.y,tmp2.x,tmp2.y);
end;

procedure printSVG2DdoubleN(a1p,a2p:p_3d);
var
  tmp1, tmp2, test1, test2, a_dir, diffp, ahp : p_3d;
  spacing : double;
  diffx, diffy : double;
begin
  {$IFDEF debug}
  debugoutput('entering printSVG2DdoubleN');
  {$ENDIF}
  tmp1.x := (a1p.x + a2p.x) / 2;
  tmp1.y := (a1p.y + a2p.y) / 2;
  tmp1.z := (a1p.z + a2p.z) / 2;
  diffx := tmp1.x - a1p.x;
  diffy := tmp1.y - a1p.y;
  ahp.x := tmp1.x - diffy;
  ahp.y := tmp1.y + diffx;
  ahp.z := tmp1.z;
  tmp1  := cross_prod(a1p,a2p,ahp);
  test1 := cross_prod(a1p,a2p,tmp1);
  test2 := cross_prod(a1p,tmp1,a2p);
  if (dist3d(ahp,test1) < dist3d(ahp,test1)) then 
    a_dir := test1 else a_dir := test2;
  spacing := std_bondlength * db_spacingfactor;
  tmp1 := new_p3(a1p,a_dir,0.5*spacing);
  diffp := subtract_3d(tmp1,a1p);
  tmp2 := add_3d(a2p,diffp);
  printSVGsingle(tmp1.x,tmp1.y,tmp2.x,tmp2.y);
  tmp1  := subtract_3d(a1p,diffp);
  tmp2  := subtract_3d(a2p,diffp);
  printSVGsingle(tmp1.x,tmp1.y,tmp2.x,tmp2.y);
end;

procedure printPS2Dtriple(a1p,a2p:p_3d);
var
  la1p, la2p, tmp1, tmp2, test1, a_dir, diffp, ahp : p_3d;
  spacing : double;
begin
  {$IFDEF debug}
  debugoutput('entering printPS2triple');
  {$ENDIF}
  la1p := a1p; la2p := a2p;
  la1p.z := 0; la2p.z := 0;  // make it flat
  ahp.x := a1p.x;
  ahp.y := a1p.y;
  ahp.z := 2;
  test1 := cross_prod(la1p,la2p,ahp);
  a_dir := test1;  // else a_dir := test2;
  spacing := std_bondlength * db_spacingfactor;
  tmp1 := new_p3(la1p,a_dir,spacing);
  diffp := subtract_3d(tmp1,la1p);
  tmp2 := add_3d(la2p,diffp);
  printPSsingle(tmp1.x,tmp1.y,tmp2.x,tmp2.y);
  tmp1  := subtract_3d(la1p,diffp);
  tmp2  := subtract_3d(la2p,diffp);
  printPSsingle(tmp1.x,tmp1.y,tmp2.x,tmp2.y);
  printPSsingle(la1p.x,la1p.y,la2p.x,la2p.y);
end;

procedure printSVG2Dtriple(a1p,a2p:p_3d);
var
  la1p, la2p, tmp1, tmp2, test1, a_dir, diffp, ahp : p_3d;
  spacing : double;
begin
  {$IFDEF debug}
  debugoutput('entering printSVG2Dtriple');
  {$ENDIF}
  la1p := a1p; la2p := a2p;
  la1p.z := 0; la2p.z := 0;  // make it flat
  ahp.x := a1p.x;
  ahp.y := a1p.y;
  ahp.z := 2;
  test1 := cross_prod(la1p,la2p,ahp);
  a_dir := test1;  // else a_dir := test2;
  spacing := std_bondlength * db_spacingfactor;
  tmp1 := new_p3(la1p,a_dir,spacing);
  diffp := subtract_3d(tmp1,la1p);
  tmp2 := add_3d(la2p,diffp);
  printSVGsingle(tmp1.x,tmp1.y,tmp2.x,tmp2.y);
  tmp1  := subtract_3d(la1p,diffp);
  tmp2  := subtract_3d(la2p,diffp);
  printSVGsingle(tmp1.x,tmp1.y,tmp2.x,tmp2.y);
  printSVGsingle(la1p.x,la1p.y,la2p.x,la2p.y);
end;

procedure printPS3DdoubleN(a1p,a2p,ahp:p_3d);
var
  tmp1, tmp2, test1, test2, a_dir, diffp : p_3d;
  spacing : double;
  angle : double;
  angle_deg : double;
begin
  {$IFDEF debug}
  debugoutput('entering printPS3DdoubleN');
  {$ENDIF}
  angle := angle_3d(a1p,a2p,ahp);
  angle_deg := radtodeg(angle);
  if (abs(angle_deg) < 5) or (abs(angle_deg) > 175) then  // unusable handle
    begin
      printPS2DdoubleN(a1p,a2p);
    end else
    begin
      tmp1  := cross_prod(a1p,a2p,ahp);
      test1 := cross_prod(a1p,a2p,tmp1);
      test2 := cross_prod(a1p,tmp1,a2p);
      if (dist3d(ahp,test1) < dist3d(ahp,test1)) then 
        a_dir := test1 else a_dir := test2;
      spacing := std_bondlength * db_spacingfactor;
      tmp1 := new_p3(a1p,a_dir,0.5*spacing);
      diffp := subtract_3d(tmp1,a1p);
      tmp2 := add_3d(a2p,diffp);
      printPSsingle(tmp1.x,tmp1.y,tmp2.x,tmp2.y);
      tmp1  := subtract_3d(a1p,diffp);
      tmp2  := subtract_3d(a2p,diffp);
      printPSsingle(tmp1.x,tmp1.y,tmp2.x,tmp2.y);
    end;
end;

procedure printSVG3DdoubleN(a1p,a2p,ahp:p_3d);
var
  tmp1, tmp2, test1, test2, a_dir, diffp : p_3d;
  spacing : double;
  angle : double;
  angle_deg : double;
begin
  {$IFDEF debug}
  debugoutput('entering printSVG3DdoubleN');
  {$ENDIF}
  angle := angle_3d(a1p,a2p,ahp);
  angle_deg := radtodeg(angle);
  if (abs(angle_deg) < 5) or (abs(angle_deg) > 175) then  // unusable handle
    begin
      printSVG2DdoubleN(a1p,a2p);
    end else
    begin
      tmp1  := cross_prod(a1p,a2p,ahp);
      test1 := cross_prod(a1p,a2p,tmp1);
      test2 := cross_prod(a1p,tmp1,a2p);
      if (dist3d(ahp,test1) < dist3d(ahp,test1)) then 
        a_dir := test1 else a_dir := test2;
      spacing := std_bondlength * db_spacingfactor;
      tmp1 := new_p3(a1p,a_dir,0.5*spacing);
      diffp := subtract_3d(tmp1,a1p);
      tmp2 := add_3d(a2p,diffp);
      printSVGsingle(tmp1.x,tmp1.y,tmp2.x,tmp2.y);
      tmp1  := subtract_3d(a1p,diffp);
      tmp2  := subtract_3d(a2p,diffp);
      printSVGsingle(tmp1.x,tmp1.y,tmp2.x,tmp2.y);
    end;
end;

procedure printPS3DdoubleA(a1p,a2p,ahp:p_3d);
var
  tmp1, tmp2, tmp3, tmp4, test1, test2, a_dir, diffp : p_3d;
  spacing : double;
  angle : double;
  angle_deg : double;
begin
  {$IFDEF debug}
  debugoutput('entering printPS3DdoubleA');
  {$ENDIF}
  angle := angle_3d(a1p,a2p,ahp);
  angle_deg := radtodeg(angle);
  if (abs(angle_deg) < 5) or (abs(angle_deg) > 175) then  // unusable handle
    begin
      printPSdouble(a1p.x,a1p.y,a2p.x,a2p.y);
    end else
    begin
      tmp1  := cross_prod(a1p,a2p,ahp);
      test1 := cross_prod(a1p,a2p,tmp1);
      test2 := cross_prod(a1p,tmp1,a2p);
      if (dist3d(ahp,test1) < dist3d(ahp,test1)) then 
        a_dir := test1 else a_dir := test2;
      spacing := std_bondlength * db_spacingfactor;
      tmp1 := new_p3(a1p,a_dir,spacing);
      diffp := subtract_3d(tmp1,a1p);
      tmp2 := add_3d(a2p,diffp);
      tmp3 := new_p3(tmp1,tmp2,0.4*spacing);
      tmp4 := new_p3(tmp2,tmp1,0.4*spacing);
      printPSsingle(tmp3.x,tmp3.y,tmp4.x,tmp4.y);
      printPSsingle(a1p.x,a1p.y,a2p.x,a2p.y);
    end;
end;

procedure printSVG3DdoubleA(a1p,a2p,ahp:p_3d);
var
  tmp1, tmp2, tmp3, tmp4, test1, test2, a_dir, diffp : p_3d;
  spacing : double;
  angle : double;
  angle_deg : double;
begin
  {$IFDEF debug}
  debugoutput('entering printPS3DdoubleA');
  {$ENDIF}
  angle := angle_3d(a1p,a2p,ahp);
  angle_deg := radtodeg(angle);
  if (abs(angle_deg) < 5) or (abs(angle_deg) > 175) then  // unusable handle
    begin
      printSVGdouble(a1p.x,a1p.y,a2p.x,a2p.y);
    end else
    begin
      tmp1  := cross_prod(a1p,a2p,ahp);
      test1 := cross_prod(a1p,a2p,tmp1);
      test2 := cross_prod(a1p,tmp1,a2p);
      if (dist3d(ahp,test1) < dist3d(ahp,test1)) then 
        a_dir := test1 else a_dir := test2;
      spacing := std_bondlength * db_spacingfactor;
      tmp1 := new_p3(a1p,a_dir,spacing);
      diffp := subtract_3d(tmp1,a1p);
      tmp2 := add_3d(a2p,diffp);
      tmp3 := new_p3(tmp1,tmp2,0.4*spacing);
      tmp4 := new_p3(tmp2,tmp1,0.4*spacing);
      printSVGsingle(tmp3.x,tmp3.y,tmp4.x,tmp4.y);
      printSVGsingle(a1p.x,a1p.y,a2p.x,a2p.y);
    end;
end;

procedure printPS3Darom(a1p,a2p,ahp:p_3d);
var
  tmp1, tmp2, tmp3, tmp4, test1, test2, a_dir, diffp : p_3d;
  spacing : double;
  angle : double;
  angle_deg : double;
begin
  {$IFDEF debug}
  debugoutput('entering printPS3Darom');
  {$ENDIF}
  angle := angle_3d(a1p,a2p,ahp);
  angle_deg := radtodeg(angle);
  if (abs(angle_deg) < 5) or (abs(angle_deg) > 175) then  // unusable handle
    begin
      printPSdouble(a1p.x,a1p.y,a2p.x,a2p.y);
    end else
    begin
      tmp1  := cross_prod(a1p,a2p,ahp);
      test1 := cross_prod(a1p,a2p,tmp1);
      test2 := cross_prod(a1p,tmp1,a2p);
      if (dist3d(ahp,test1) < dist3d(ahp,test1)) then 
        a_dir := test1 else a_dir := test2;
      spacing := std_bondlength * db_spacingfactor;
      tmp1 := new_p3(a1p,a_dir,spacing);
      diffp := subtract_3d(tmp1,a1p);
      tmp2 := add_3d(a2p,diffp);
      // shortening of bond
      tmp3 := new_p3(tmp1,tmp2,0.7*spacing);
      tmp4 := new_p3(tmp2,tmp1,0.7*spacing);
      writeouts(' stroke [3 3] 1 setdash 0.3 setgray ');
      printPSsingle(tmp3.x,tmp3.y,tmp4.x,tmp4.y);
      writeouts(' stroke [] 0 setdash 0.0 setgray ');
      printPSsingle(a1p.x,a1p.y,a2p.x,a2p.y);
    end;
end;

procedure printSVG3Darom(a1p,a2p,ahp:p_3d);
var
  tmp1, tmp2, tmp3, tmp4, test1, test2, a_dir, diffp : p_3d;
  spacing : double;
  angle : double;
  angle_deg : double;
  outx,outy : single;
  bstr : string;
begin
  {$IFDEF debug}
  debugoutput('entering printSVG3Darom');
  {$ENDIF}
  angle := angle_3d(a1p,a2p,ahp);
  angle_deg := radtodeg(angle);
  if (abs(angle_deg) < 5) or (abs(angle_deg) > 175) then  // unusable handle
    begin
      printSVGdouble(a1p.x,a1p.y,a2p.x,a2p.y);
    end else
    begin
      tmp1  := cross_prod(a1p,a2p,ahp);
      test1 := cross_prod(a1p,a2p,tmp1);
      test2 := cross_prod(a1p,tmp1,a2p);
      if (dist3d(ahp,test1) < dist3d(ahp,test1)) then 
        a_dir := test1 else a_dir := test2;
      spacing := std_bondlength * db_spacingfactor;
      tmp1 := new_p3(a1p,a_dir,spacing);
      diffp := subtract_3d(tmp1,a1p);
      tmp2 := add_3d(a2p,diffp);
      // shortening of bond
      tmp3 := new_p3(tmp1,tmp2,0.7*spacing);
      tmp4 := new_p3(tmp2,tmp1,0.7*spacing);
      // the dotted line first
      outX := (tmp3.x+xoffset)*blfactor*svg_factor;
      outY := (tmp3.y+yoffset)*blfactor*-svg_factor + svg_yoffset;
      chk_svg_max_xy(outX,outY);
      bstr := format('<line stroke-dasharray="3,3" x1="%1.1f" y1="%1.1f" ',[outx,outy],fsettings);
      outX := (tmp4.x+xoffset)*blfactor*svg_factor;
      outY := (tmp4.y+yoffset)*blfactor*-svg_factor + svg_yoffset;
      chk_svg_max_xy(outX,outY);
      bstr := bstr + format('x2="%1.1f" y2="%1.1f" />',[outx,outy],fsettings);
      writeouts(bstr);
      // the solid line next
      outX := (a1p.x+xoffset)*blfactor*svg_factor;
      outY := (a1p.y+yoffset)*blfactor*-svg_factor + svg_yoffset;
      chk_svg_max_xy(outX,outY);
      bstr := format('<line x1="%1.1f" y1="%1.1f" ',[outx,outy],fsettings);
      outX := (a2p.x+xoffset)*blfactor*svg_factor;
      outY := (a2p.y+yoffset)*blfactor*-svg_factor + svg_yoffset;
      chk_svg_max_xy(outX,outY);
      bstr := bstr + format('x2="%1.1f" y2="%1.1f" />',[outx,outy],fsettings);
      writeouts(bstr);
    end;
end;

procedure printPS2Dwedge(a1p,a2p:p_3d);
var
  la1p, la2p, tmp1, tmp2, test1, a_dir, diffp, ahp : p_3d;
  spacing : double;
  outxint, outyint : integer;
begin
  {$IFDEF debug}
  debugoutput('entering printPS2Dwedge');
  {$ENDIF}
  la1p := a1p; la2p := a2p;
  la1p.z := 0; la2p.z := 0;  // make it flat
  ahp.x := a1p.x;
  ahp.y := a1p.y;
  ahp.z := 2;
  test1 := cross_prod(la1p,la2p,ahp);
  a_dir := test1;
  spacing := std_bondlength * db_spacingfactor * 0.6;
  tmp1 := new_p3(la1p,a_dir,spacing);
  diffp := subtract_3d(tmp1,la1p);
  tmp1 := add_3d(la2p,diffp);
  tmp2  := subtract_3d(la2p,diffp);
  outXint := round((la1p.x+xoffset)*blfactor);
  outYint := round((la1p.y+yoffset)*blfactor);
  updatebb(outXint, outYint);
  writeouts('stroke');
  writeouts('0 setlinejoin 10 setmiterlimit');
  writeouts('newpath');
  writeout('%d dot %d dot moveto', [outxint, outyint]);
  outXint := round((tmp1.x+xoffset)*blfactor);
  outYint := round((tmp1.y+yoffset)*blfactor);
  updatebb(outXint, outYint);
  writeout('%d dot %d dot lineto', [outxint, outyint]);
  outXint := round((tmp2.x+xoffset)*blfactor);
  outYint := round((tmp2.y+yoffset)*blfactor);
  updatebb(outXint, outYint);
  writeout('%d dot %d dot lineto', [outxint, outyint]);
  outXint := round((la1p.x+xoffset)*blfactor);
  outYint := round((la1p.y+yoffset)*blfactor);
  updatebb(outXint, outYint);
  writeout('%d dot %d dot lineto', [outxint, outyint]);
  writeouts('closepath fill 1 setlinejoin');
  writeouts('stroke');
end;

procedure printSVG2Dwedge(a1p,a2p:p_3d);
var
  la1p, la2p, tmp1, tmp2, test1, a_dir, diffp, ahp : p_3d;
  spacing : double;
  outx, outy : double;
  bstr : string;
begin
  {$IFDEF debug}
  debugoutput('entering printSVG2Dwedge');
  {$ENDIF}
  la1p := a1p; la2p := a2p;
  la1p.z := 0; la2p.z := 0;  // make it flat
  ahp.x := a1p.x;
  ahp.y := a1p.y;
  ahp.z := 2;
  test1 := cross_prod(la1p,la2p,ahp);
  a_dir := test1;
  spacing := std_bondlength * db_spacingfactor * 0.6;
  tmp1 := new_p3(la1p,a_dir,spacing);
  diffp := subtract_3d(tmp1,la1p);
  tmp1 := add_3d(la2p,diffp);
  tmp2  := subtract_3d(la2p,diffp);
  outX := (la1p.x+xoffset)*blfactor*svg_factor;
  outY := (la1p.y+yoffset)*blfactor*-svg_factor + svg_yoffset;
  chk_svg_max_xy(outX,outY);
  bstr := format('<polygon points="%1.1f,%1.1f ',[outx,outy],fsettings);
  outX := (tmp1.x+xoffset)*blfactor*svg_factor;
  outY := (tmp1.y+yoffset)*blfactor*-svg_factor + svg_yoffset;
  chk_svg_max_xy(outX,outY);
  bstr := bstr + format('%1.1f,%1.1f ',[outx,outy],fsettings);
  outX := (tmp2.x+xoffset)*blfactor*svg_factor;
  outY := (tmp2.y+yoffset)*blfactor*-svg_factor + svg_yoffset;
  chk_svg_max_xy(outX,outY);
  bstr := bstr + format('%1.1f,%1.1f ',[outx,outy],fsettings);
  outX := (la1p.x+xoffset)*blfactor*svg_factor;
  outY := (la1p.y+yoffset)*blfactor*-svg_factor + svg_yoffset;
  chk_svg_max_xy(outX,outY);
  bstr := bstr + format('%1.1f,%1.1f"',[outx,outy],fsettings);
  writeouts(bstr);
  writeouts('style="fill:#000000; stroke:#000000;stroke-width:1; stroke-linejoin:round"/> ');
end;

procedure printPS2Dhatch(a1p,a2p:p_3d);
var
  la1p, la2p, tmp1, tmp2, tmp3, tmp4, test1, test2, test3, test4 : p_3d;
  diffp, ahp, w1, w2 : p_3d;
  spacing : double;
  i, nstrips : integer;
  dist2d, step : double;
begin
  {$IFDEF debug}
  debugoutput('entering printPS22Dhatch');
  {$ENDIF}
  la1p := a1p; la2p := a2p;
  la1p.z := 0; la2p.z := 0;  // make it flat
  ahp.x := a1p.x;
  ahp.y := a1p.y;
  ahp.z := 2;
  test1 := cross_prod(la1p,la2p,ahp);
  test2 := cross_prod(la1p,ahp,la2p);
  spacing := std_bondlength * db_spacingfactor * 0.6;
  tmp1 := new_p3(la1p,test1,spacing*0.3);
  tmp2 := new_p3(la1p,test2,spacing*0.3);
  diffp := subtract_3d(la2p,la1p);
  test3 := add_3d(test1,diffp);
  test4 := add_3d(test2,diffp);
  tmp3 := new_p3(la2p,test3,spacing*1.1);
  tmp4 := new_p3(la2p,test4,spacing*1.1);
  dist2d := dist3d(la1p,la2p);
  nstrips := 6;
  if dist2d < 0.8 * std_blCCsingle then nstrips := 5;
  if dist2d < 0.6 * std_blCCsingle then nstrips := 4;
  if dist2d < 0.4 * std_blCCsingle then nstrips := 3;
  if dist2d < 0.2 * std_blCCsingle then nstrips := 2;
  step := dist2d / nstrips;
  for i := 1 to nstrips do
    begin
      w1 := new_p3(tmp1,tmp3,(i*step - step*0.4));
      w2 := new_p3(tmp2,tmp4,(i*step - step*0.4));
      printPSsingle(w1.x,w1.y,w2.x,w2.y);
    end;
end;

procedure printSVG2Dhatch(a1p,a2p:p_3d);
var
  la1p, la2p, tmp1, tmp2, tmp3, tmp4, test1, test2, test3, test4 : p_3d;
  diffp, ahp, w1, w2 : p_3d;
  spacing : double;
  i, nstrips : integer;
  dist2d, step : double;
begin  // must be always processed in svg_mode 1
  {$IFDEF debug}
  debugoutput('entering printSVG2Dhatch');
  {$ENDIF}
  la1p := a1p; la2p := a2p;
  la1p.z := 0; la2p.z := 0;  // make it flat
  ahp.x := a1p.x;
  ahp.y := a1p.y;
  ahp.z := 2;
  test1 := cross_prod(la1p,la2p,ahp);
  test2 := cross_prod(la1p,ahp,la2p);
  spacing := std_bondlength * db_spacingfactor * 0.6;
  tmp1 := new_p3(la1p,test1,spacing*0.3);
  tmp2 := new_p3(la1p,test2,spacing*0.3);
  diffp := subtract_3d(la2p,la1p);
  test3 := add_3d(test1,diffp);
  test4 := add_3d(test2,diffp);
  tmp3 := new_p3(la2p,test3,spacing*1.1);
  tmp4 := new_p3(la2p,test4,spacing*1.1);
  dist2d := dist3d(la1p,la2p);
  nstrips := 6;
  if dist2d < 0.8 * std_blCCsingle then nstrips := 5;
  if dist2d < 0.6 * std_blCCsingle then nstrips := 4;
  if dist2d < 0.4 * std_blCCsingle then nstrips := 3;
  if dist2d < 0.2 * std_blCCsingle then nstrips := 2;
  step := dist2d / nstrips;
  for i := 1 to nstrips do
    begin
      w1 := new_p3(tmp1,tmp3,(i*step - step*0.4));
      w2 := new_p3(tmp2,tmp4,(i*step - step*0.4));
      printSVGsingle(w1.x,w1.y,w2.x,w2.y);
    end;
end;

procedure printPScomplex(X1,Y1,X2,Y2 : single);  // v0.1c
var
  outXint, outYint, outXint2, outYint2 : integer;
begin
  outXint := round((X1+xoffset)*blfactor);
  outYint := round((Y1+yoffset)*blfactor);
  updatebb(outXint, outYint);
  writeouts(' stroke [3 3] 1 setdash 0.7 setgray ');
  outXint2 := round((X2+xoffset)*blfactor);
  outYint2 := round((Y2+yoffset)*blfactor);
  updatebb(outXint2, outYint2);
  writeout('%d dot %d dot moveto %d dot %d dot lineto', [outXint, outYint, outXint2, outYint2]);
  writeouts(' stroke [] 0 setdash 0.0 setgray ');
end;

procedure printSVGcomplex(X1,Y1,X2,Y2 : single);  // v0.1c
var
  outX, outY : single;
  bstr : string;
begin
  outX := (X1+xoffset)*blfactor*svg_factor;
  outY := (Y1+yoffset)*blfactor*-svg_factor + svg_yoffset;
  chk_svg_max_xy(outX,outY);
  bstr := format('<line stroke="#777777" stroke-width="%1.1f" stroke-dasharray="2,2" x1="%1.1f" y1="%1.1f" ',[linewidth,outx,outy],fsettings); 
  outX := (X2+xoffset)*blfactor*svg_factor;
  outY := (Y2+yoffset)*blfactor*-svg_factor + svg_yoffset;
  chk_svg_max_xy(outX,outY);
  bstr := bstr + format('x2="%1.1f" y2="%1.1f" />',[outx,outy],fsettings);
  writeouts(bstr);
end;

procedure printPSarrow(X1,Y1,X2,Y2 : single);   // v0.2
var
  origbitX1, origbitY1, origbitX2, origbitY2 : single;
  r, topX, topY, coarsedeltaX, coarsedeltaY, finedeltaX, finedeltaY,
  deltaX, deltaY, leftX, leftY, rightX, rightY : single;
  outXint, outYint : integer;
begin
  origbitX1 := X1;
  origbitY1 := Y1;
  origbitX2 := X2;
  origbitY2 := Y2;
  writeout('%1.1f setlinewidth',[2*linewidth]);
  printPSsingle(X1,Y1,X2,Y2);
  writeouts('stroke');
  writeout('%1.1f setlinewidth',[linewidth]);
  r := sqrt(sqr(origbitX1-origbitX2)+sqr(origbitY1-origbitY2));
  coarsedeltaX := 0.4*PX*(origbitY2-origbitY1)/(2*r);
  coarsedeltaY := 0.4*PX*(origbitX2-origbitX1)/(2*r);
  finedeltaX := 3*coarsedeltaY;
  finedeltaY := 3*coarsedeltaX;
  deltaX := (coarsedeltaX - finedeltaX);
  deltaY := (coarsedeltaY + finedeltaY);
  leftX := origbitX2 + deltaX;
  leftY := origbitY2 - deltaY;
  deltaX := (coarsedeltaX + finedeltaX);
  deltaY := (coarsedeltaY - finedeltaY);
  rightX := origbitX2 - deltaX;
  rightY := origbitY2 + deltaY;
  topX := origbitX2 + 0.1*PX*(origbitX2-origbitX1)/r;
  topY := origbitY2 + 0.1*PX*(origbitY2-origbitY1)/r;
  outXint := round((topX+xoffset)*blfactor);
  outYint := round((topY+yoffset)*blfactor);
  writeouts('0 setlinejoin 10 setmiterlimit');
  writeouts('newpath');
  writeout('%d dot %d dot moveto',[outXint,outYint]);
  outXint := round((leftX+xoffset)*blfactor);
  outYint := round((leftY+yoffset)*blfactor);
  writeout('%d dot %d dot lineto',[outXint,outYint]);
  outXint := round((rightX+xoffset)*blfactor);
  outYint := round((rightY+yoffset)*blfactor);
  writeout('%d dot %d dot lineto',[outXint,outYint]);
  outXint := round((topX+xoffset)*blfactor);
  outYint := round((topY+yoffset)*blfactor);
  writeout('%d dot %d dot lineto',[outXint,outYint]);
  writeouts('closepath fill 1 setlinejoin');
end;

procedure printSVGarrow(X1,Y1,X2,Y2 : single);   // v0.2
var
  origbitX1, origbitY1, origbitX2, origbitY2 : single;
  r, topX, topY, coarsedeltaX, coarsedeltaY, finedeltaX, finedeltaY,
  deltaX, deltaY, leftX, leftY, rightX, rightY : single;
  outX, outY : single;
  bstr : string;
begin
  origbitX1 := X1;
  origbitY1 := Y1;
  origbitX2 := X2;
  origbitY2 := Y2;
  outX := (X1+xoffset)*blfactor*svg_factor;
  outY := (Y1+yoffset)*blfactor*-svg_factor + svg_yoffset;
  chk_svg_max_xy(outX,outY);
  bstr := format('<line style="stroke-width: %1.1f;" x1="%1.1f" y1="%1.1f" ',[(2*linewidth),outx,outy],fsettings); 
  outX := (X2+xoffset)*blfactor*svg_factor;
  outY := (Y2+yoffset)*blfactor*-svg_factor + svg_yoffset;
  chk_svg_max_xy(outX,outY);
  bstr := bstr + format('x2="%1.1f" y2="%1.1f" />',[outx,outy],fsettings);
  writeouts(bstr);
  r := sqrt(sqr(origbitX1-origbitX2)+sqr(origbitY1-origbitY2));
  coarsedeltaX := 0.4*PX*(origbitY2-origbitY1)/(2*r);
  coarsedeltaY := 0.4*PX*(origbitX2-origbitX1)/(2*r);
  finedeltaX := 3*coarsedeltaY;
  finedeltaY := 3*coarsedeltaX;
  deltaX := (coarsedeltaX - finedeltaX);
  deltaY := (coarsedeltaY + finedeltaY);
  leftX := origbitX2 + deltaX;
  leftY := origbitY2 - deltaY;
  deltaX := (coarsedeltaX + finedeltaX);
  deltaY := (coarsedeltaY - finedeltaY);
  rightX := origbitX2 - deltaX;
  rightY := origbitY2 + deltaY;
  topX := origbitX2 + 0.1*PX*(origbitX2-origbitX1)/r;
  topY := origbitY2 + 0.1*PX*(origbitY2-origbitY1)/r;
  outX := (topX+xoffset)*blfactor*svg_factor;
  outY := (topY+yoffset)*blfactor*-svg_factor + svg_yoffset;
  chk_svg_max_xy(outX,outY);
  bstr := format('<polygon points="%1.1f,%1.1f ',[outx,outy],fsettings);
  outX := (leftX+xoffset)*blfactor*svg_factor;
  outY := (leftY+yoffset)*blfactor*-svg_factor + svg_yoffset;
  chk_svg_max_xy(outX,outY);
  bstr := bstr + format('%1.1f,%1.1f ',[outx,outy],fsettings);
  outX := (rightX+xoffset)*blfactor*svg_factor;
  outY := (rightY+yoffset)*blfactor*-svg_factor + svg_yoffset;
  chk_svg_max_xy(outX,outY);
  bstr := bstr + format('%1.1f,%1.1f ',[outx,outy],fsettings);
  outX := (topX+xoffset)*blfactor*svg_factor;
  outY := (topY+yoffset)*blfactor*-svg_factor + svg_yoffset;
  chk_svg_max_xy(outX,outY);
  bstr := bstr + format('%1.1f,%1.1f" ',[outx,outy],fsettings);
  bstr := bstr + 'style="fill:#000000; stroke:#000000;stroke-width:1"/> '; 
  writeouts(bstr);
end;

procedure print_PS_squarebracket(x1,y1,x2,y2,x3,y3,x4,y4 : single;brlabel:string);  // v0.1f
var
  xmax, ymin, xtmp : single;
  width : single;
  outXint, outYint : integer;
begin
  width := 0.2;
  xmax := -9999; 
  if x1 > xmax then xmax := x1; if x2 > xmax then xmax := x2;
  if x3 > xmax then xmax := x3; if x4 > xmax then xmax := x4;
  ymin := 9999; 
  if y1 < ymin then ymin := y1; if y2 < ymin then ymin := y2;
  if y3 < ymin then ymin := y3; if y4 < ymin then ymin := y4;
  printPSsingle(x1,y1,x2,y2);
  if x3 > x1 then xtmp := x1 + width else xtmp := x1 - width;
  printPSsingle(x1,y1,xtmp,y1);
  if x4 > x2 then xtmp := x2 + width else xtmp := x2 - width;
  printPSsingle(x2,y2,xtmp,y2);
  printPSsingle(x3,y3,x4,y4);
  if x3 > x1 then xtmp := x3 - width else xtmp := x3 + width;
  printPSsingle(x3,y3,xtmp,y3);
  if x4 > x2 then xtmp := x4 - width else xtmp := x4 + width;
  printPSsingle(x4,y4,xtmp,y4);
  outXint := round((xmax+width+xoffset)*blfactor);
  outYint := round((ymin+yoffset)*blfactor);
  writeout('%d dot %d dot moveto ',[outXint,outYint]);
  updatebb(outXint, outYint);
  writeouts('CFontSub');
  writeouts('('+brlabel+') show');
  outXint := outXint + round(2.0*get_stringwidth(fontsize2,brlabel));
  updatebb(outXint, outYint);
end;

procedure print_SVG_squarebracket(x1,y1,x2,y2,x3,y3,x4,y4 : single;brlabel:string);  // v0.1f
var
  xmax, ymin, xtmp : single;
  width : single;
  outX, outY : single;
  bstr : string;
begin   // must be always processed in svg_mode = 1
  width := 0.2;
  xmax := -9999; 
  if x1 > xmax then xmax := x1; if x2 > xmax then xmax := x2;
  if x3 > xmax then xmax := x3; if x4 > xmax then xmax := x4;
  ymin := 9999; 
  if y1 < ymin then ymin := y1; if y2 < ymin then ymin := y2;
  if y3 < ymin then ymin := y3; if y4 < ymin then ymin := y4;
  printSVGsingle(x1,y1,x2,y2);
  if x3 > x1 then xtmp := x1 + width else xtmp := x1 - width;
  printSVGsingle(x1,y1,xtmp,y1);
  if x4 > x2 then xtmp := x2 + width else xtmp := x2 - width;
  printSVGsingle(x2,y2,xtmp,y2);
  printSVGsingle(x3,y3,x4,y4);
  if x3 > x1 then xtmp := x3 - width else xtmp := x3 + width;
  printSVGsingle(x3,y3,xtmp,y3);
  if x4 > x2 then xtmp := x4 - width else xtmp := x4 + width;
  printSVGsingle(x4,y4,xtmp,y4);
  outX := (xmax+width+xoffset)*blfactor*svg_factor;
  outY := (ymin+yoffset)*blfactor*-svg_factor + svg_yoffset;
  chk_svg_max_xy(outX,outY);  // 
  bstr := format('<text style="font-size: %dpx" text-anchor="start" x="%1.1f" y="%1.1f">%s</text>',[fontsize2,outx,outy,brlabel],fsettings);
  writeouts(bstr);
  outX := outX + 0.6*get_stringwidth(fontsize2,brlabel);
  chk_svg_max_xy(outX,outY);
end;

procedure print_PS_bond(i:integer);
var
  a1, a2, ah : integer;
  a1x, a1y, a1z, a2x, a2y, a2z, ahx, ahy, ahz : single;
  a1p, a2p, ahp : p_3d;
  bt, bst : char;
begin
  if (n_bonds = 0) or (i > n_bonds) then exit;
  a1  := bond^[i].a1;
  a2  := bond^[i].a2;
  ah  := bond^[i].a_handle;
  bt  := bond^[i].btype;
  bst := bond^[i].bsubtype;
  a1x := atom^[a1].x;
  a1y := atom^[a1].y;
  a1z := atom^[a1].z;
  a2x := atom^[a2].x;
  a2y := atom^[a2].y;
  a2z := atom^[a2].z;
  a1p.x := a1x; a1p.y := a1y; a1p.z := a1z;
  a2p.x := a2x; a2p.y := a2y; a2p.z := a2z;
  if (bond^[i].hidden = false) then
    begin
      {$IFDEF debug}
      debugoutput('printing bond '+inttostr(i)+', atom 1: '+inttostr(a1)+', atom 2: '+inttostr(a2));
      {$ENDIF}
      if (ah = 0) then
        begin
          if bt = 'S' then 
            begin
              if bst = 'N' then printPSsingle(a1x,a1y,a2x,a2y);
              if bst = 'W' then printPS2Dwedge(a1p,a2p);
              if bst = 'H' then printPS2Dhatch(a1p,a2p);
            end;  
          if bt = 'D' then printPS2DdoubleN(a1p,a2p);
          if bt = 'T' then printPS2Dtriple(a1p,a2p);
          if bt = 'A' then printPSsingle(a1x,a1y,a2x,a2y);
          if bt = 'C' then printPScomplex(a1x,a1y,a2x,a2y);
          if bt = 'a' then printPScomplex(a1x,a1y,a2x,a2y);  // v0.2b
        end else
        begin
          ahx := atom^[ah].x;
          ahy := atom^[ah].y;
          ahz := atom^[ah].z;
          ahp.x := ahx; ahp.y := ahy; ahp.z := ahz;
          if (bt = 'D') and (bst = 'N') then printPS3DdoubleN(a1p,a2p,ahp);
          if (bt = 'D') and (bst = 'A') then printPS3DdoubleA(a1p,a2p,ahp);
          if (bt = 'A') then printPS3Darom(a1p,a2p,ahp);
        end;
      //writeln('stroke');
    end;
end;

procedure print_SVG_bond(i:integer);
var
  a1, a2, ah : integer;
  a1x, a1y, a1z, a2x, a2y, a2z, ahx, ahy, ahz : single;
  a1p, a2p, ahp : p_3d;
  bt, bst : char;
begin
  if (n_bonds = 0) or (i > n_bonds) then exit;
  a1  := bond^[i].a1;
  a2  := bond^[i].a2;
  ah  := bond^[i].a_handle;
  bt  := bond^[i].btype;
  bst := bond^[i].bsubtype;
  a1x := atom^[a1].x;
  a1y := atom^[a1].y;
  a1z := atom^[a1].z;
  a2x := atom^[a2].x;
  a2y := atom^[a2].y;
  a2z := atom^[a2].z;
  a1p.x := a1x; a1p.y := a1y; a1p.z := a1z;
  a2p.x := a2x; a2p.y := a2y; a2p.z := a2z;
  if (bond^[i].hidden = false) then
    begin
      {$IFDEF debug}
      debugoutput('printing bond '+inttostr(i)+', atom 1: '+inttostr(a1)+', atom 2: '+inttostr(a2));
      {$ENDIF}
      if (ah = 0) then
        begin
          if bt = 'S' then 
            begin
              if bst = 'N' then printSVGsingle(a1x,a1y,a2x,a2y);
              if bst = 'W' then printSVG2Dwedge(a1p,a2p);
              if bst = 'H' then printSVG2Dhatch(a1p,a2p);
            end;  
          if bt = 'D' then printSVG2DdoubleN(a1p,a2p);
          if bt = 'T' then printSVG2Dtriple(a1p,a2p);
          if bt = 'A' then printSVGsingle(a1x,a1y,a2x,a2y);
          if bt = 'C' then printSVGcomplex(a1x,a1y,a2x,a2y);
          if bt = 'a' then printSVGcomplex(a1x,a1y,a2x,a2y);  // v0.2b
        end else
        begin
          ahx := atom^[ah].x;
          ahy := atom^[ah].y;
          ahz := atom^[ah].z;
          ahp.x := ahx; ahp.y := ahy; ahp.z := ahz;
          if (bt = 'D') and (bst = 'N') then printSVG3DdoubleN(a1p,a2p,ahp);
          if (bt = 'D') and (bst = 'A') then printSVG3DdoubleA(a1p,a2p,ahp);
          if (bt = 'A') then printSVG3Darom(a1p,a2p,ahp);
        end;
    end;
end;

procedure print_SVG_bond_special(i:integer);
var
  a1, a2, ah : integer;
  a1x, a1y, a1z, a2x, a2y, a2z, ahx, ahy, ahz : single;
  a1p, a2p, ahp : p_3d;
  bt, bst : char;
begin
  if (n_bonds = 0) or (i > n_bonds) then exit;
  a1  := bond^[i].a1;
  a2  := bond^[i].a2;
  ah  := bond^[i].a_handle;
  bt  := bond^[i].btype;
  bst := bond^[i].bsubtype;
  a1x := atom^[a1].x;
  a1y := atom^[a1].y;
  a1z := atom^[a1].z;
  a2x := atom^[a2].x;
  a2y := atom^[a2].y;
  a2z := atom^[a2].z;
  a1p.x := a1x; a1p.y := a1y; a1p.z := a1z;
  a2p.x := a2x; a2p.y := a2y; a2p.z := a2z;
  if (bond^[i].hidden = false) then
    begin
      {$IFDEF debug}
      debugoutput('printing special bond '+inttostr(i)+', atom 1: '+inttostr(a1)+', atom 2: '+inttostr(a2)+bt+bst);
      {$ENDIF}
      if (ah = 0) then
        begin
          if bt = 'S' then 
            begin
              if bst = 'W' then begin printSVG2Dwedge(a1p,a2p); bond^[i].drawn := true; end;
            end;  
          if bt = 'C' then begin printSVGcomplex(a1x,a1y,a2x,a2y); bond^[i].drawn := true; end;
          if bt = 'a' then begin printSVGcomplex(a1x,a1y,a2x,a2y); bond^[i].drawn := true; end;
        end else
        begin
          ahx := atom^[ah].x;
          ahy := atom^[ah].y;
          ahz := atom^[ah].z;
          ahp.x := ahx; ahp.y := ahy; ahp.z := ahz;
          if (bt = 'A') then begin printSVG3Darom(a1p,a2p,ahp); bond^[i].drawn := true; end;
        end;
    end;
end;


procedure printBB(x,y : integer; anchor, chgstr : string);
begin
  if (anchor = '') then exit;
  writeouts('stroke');
  writeout('/X {%d} def',[x]);
  writeout('/Y {%d} def',[y]);
  writeouts('/anchor ('+anchor+') def');
  if (chgstr = '') then writeouts('bb') else writeouts ('bbx');
end;


procedure printSVGBB(x,y : single; anchor, chgstr : string);
var
  r : single;
begin
  r := fontsize1 * 0.45;
  if (anchor = '') then exit;
  
  (* example for a rectangle in SVG
  <rect x="20" y="20" width="250" height="250"
  style="fill:blue;stroke:pink;stroke-width:5;
  fill-opacity:0.1;stroke-opacity:0.9"/>
  *)
  
  // for now, use a circle  
  writeout('<circle cx="%1.1f" cy="%1.1f" r="%1.1f" />',[x,y,r]);
  chk_svg_max_xy((x+3*r),(y+3*r));   // add a sefety margin
end;

function findHpos(a1:integer):integer;
const
  HPright = 1;
  HPleft  = 2;
  HPup    = 3;
  HPdown  = 4;
var
  i, a2 : integer;
  res : integer;
  nb : neighbor_rec;
  occupied : array[1..8] of boolean;
  a1p, a2p, refp : p_3d;
  angle, angledeg : double;
  n_occ : integer;
begin
  res := HPright;
  fillchar(occupied,sizeof(occupied),false);
  a1p.x := atom^[a1].x;
  a1p.y := atom^[a1].y;
  a1p.z := atom^[a1].z;
  refp.x := a1p.x + 2;
  refp.y := a1p.y;
  refp.z := a1p.z;
  nb := get_neighbors(a1);
  for i := 1 to atom^[a1].neighbor_count do
    begin
      a2 := nb[i];
      if is_heavyatom(a2) then
        begin
          a2p.x := atom^[a2].x;
          a2p.y := atom^[a2].y;
          a2p.z := atom^[a2].z;
          angle := angle_2d_XY(a1p,refp,a2p);
          angledeg := radtodeg(angle);
          if abs(angledeg) <= 4*dirtolerance then occupied[dir_right] := true else
            begin
              if abs(angledeg) < (90-4*dirtolerance) then
                begin
                  if a2p.y > a1p.y then occupied[dir_rightup] := true else
                                        occupied[dir_rightdown] := true;
                end else
                begin
                  if abs(angledeg) <= (90+4*dirtolerance) then
                    begin
                      if a2p.y > a1p.y then occupied[dir_up] := true else
                                            occupied[dir_down] := true;
                    end else
                    begin
                      if abs(angledeg) < (180-4*dirtolerance) then
                        begin
                          if a2p.y > a1p.y then occupied[dir_leftup] := true else
                                                occupied[dir_leftdown] := true;
                        end else
                        begin
                          occupied[dir_left] := true;                        
                        end;                    
                    end;
                end;
            end;
        end;
    end;
  // and now the assignment....  
  n_occ := 0;
  for i := 1 to 8 do if occupied[i] then inc(n_occ);
  if n_occ = 1 then
    begin
      if occupied[dir_rightup] or
         occupied[dir_right] or
         occupied[dir_rightdown] then res := HPleft else res := HPright;    
    end;
  if n_occ > 1 then
    begin
       if (occupied[dir_right] and 
          (occupied[dir_leftup] or occupied[dir_left] or occupied[dir_leftdown])) then
          begin
            if (not occupied[dir_up]) and (not occupied[dir_rightup]) and 
               (not occupied[dir_leftup]) then res := HPup;
            if (not occupied[dir_down]) and (not occupied[dir_rightdown]) and 
               (not occupied[dir_leftdown]) then res := HPdown;
            if (n_occ > 2) and (not occupied[dir_left]) then res := HPleft;  
          end; 
       if (occupied[dir_left] and 
          (occupied[dir_rightup] or occupied[dir_right] or occupied[dir_rightdown])) then
          begin
            if (not occupied[dir_up]) and (not occupied[dir_rightup]) and 
               (not occupied[dir_leftup]) then res := HPup;
            if (not occupied[dir_down]) and (not occupied[dir_rightdown]) and 
               (not occupied[dir_leftdown]) then res := HPdown;
            if (n_occ > 2) and (not occupied[dir_right]) then res := HPright;  
          end; 
       if (not occupied[dir_up]) and (occupied[dir_rightdown] or occupied[dir_right] or occupied[dir_down]) and
          (occupied[dir_leftdown] or occupied[dir_left] or occupied[dir_down]) then res := HPup;
       if (not occupied[dir_down]) and (occupied[dir_rightup] or occupied[dir_right] or occupied[dir_up]) and
          (occupied[dir_leftup] or occupied[dir_left] or occupied[dir_up]) then res := HPdown;
      if (not occupied[dir_leftup]) and
         (not occupied[dir_left]) and
         (not occupied[dir_leftdown]) then res := HPleft;
      if (not occupied[dir_rightup]) and
         (not occupied[dir_right]) and
         (not occupied[dir_rightdown]) then res := HPright;
    end;  
  findHpos := res;
end;


function lookuprgb(elstr:str2):string;
var
  i : integer;
  rval, gval, bval : single;
  tmpstr : string;
  valstr : string;
begin
  tmpstr := '0 0 0';
  for i := 1 to max_rgbentries do
    begin
      if (elstr = rgbtable[i].element) then
        begin
          rval := rgbtable[i].r / 255;
          gval := rgbtable[i].g / 255;
          bval := rgbtable[i].b / 255;
          str(rval:1:2,valstr);
          tmpstr := valstr + ' ';
          str(gval:1:2,valstr);
          tmpstr := tmpstr + valstr + ' ';
          str(bval:1:2,valstr);
          tmpstr := tmpstr + valstr;
        end;
    end;
  lookuprgb := tmpstr;
end;


function lookuprgbhex(elstr:str2):string;
var
  i : integer;
  rval, gval, bval : integer;
  tmpstr : string;
begin
  tmpstr := '000000';
  for i := 1 to max_rgbentries do
    begin
      if (elstr = rgbtable[i].element) then
        begin
          rval := rgbtable[i].r;
          gval := rgbtable[i].g;
          bval := rgbtable[i].b;
          tmpstr := inttohex(rval,2) + inttohex(gval,2) + inttohex(bval,2);
        end;
    end;
  lookuprgbhex := tmpstr;
end;

procedure printPSchars;
const
  HPright = 1;
  HPleft  = 2;
  HPup    = 3;
  HPdown  = 4;
var
  i, j : integer;
  instr : string[20];
  checkstr1, checkstr2 : string[64];
  check1len, check2len : integer;
  outXint, outYint : integer;
  strlen : integer;
  outstr : str4;
  outchar, anchor : char;
  charX, charY : integer;
  el : str2;
  tmpstr : string;
  Hpos : integer;
  Hstr : string;
  rstr, lstr : string;
  chg : integer;
  chgstr : string;
  rad : integer;
  iso : integer;
  isostr : string;
  lblstr : string;
  extrashift : double;
  rgbstr : string;
  a1, a2 : integer;
  sg : boolean;  // v0.2a
  alias : string;  // v0.2b
begin
  writeouts('stroke');
  writeouts('CFont');
  for i := 1 to n_atoms Do
    begin
      sg := false;
      if opt_sgroups then sg := atom^[i].sg;  // v0.2a
      alias := atom^[i].alias;   // v0.2b
      outstr := '    ';
      Hstr := '';
      rstr := '';
      lstr := '';
      chg := 0;
      chgstr := '';
      isostr := '';
      el := atom^[i].element;
      tmpstr := lowercase(el);
      tmpstr[1] := upcase(tmpstr[1]);
      instr := tmpstr;
      if (instr[2] = ' ') then delete(instr,2,1);
      if opt_color then 
        begin
          rgbstr := lookuprgb(instr);
          if (el = 'H ') then
            begin
              if (atom^[i].nucleon_number = 2) then rgbstr := lookuprgb('D');
              if (atom^[i].nucleon_number = 3) then rgbstr := lookuprgb('T');
            end;
        end;
      lblstr := instr;
      {$IFDEF debug}
      debugoutput('atom '+inttostr(i)+': Hexp = '+inttostr(atom^[i].Hexp)+' Htot = '+ inttostr(atom^[i].Htot));  // v0.1f
      {$ENDIF}
      charX := round((atom^[i].x+xoffset)*blfactor);
      charY := round((atom^[i].y+yoffset)*blfactor);
      outXint := charX;
      outYint := charY - round(fontsize1*1.5);  // was: 20
      updatebb(outXint, outYint);
      strlen := length(instr);
      Hpos := HPright;  // default
      if (opt_Honhetero and is_electroneg(uppercase(el))) or
         (opt_Honhetero and is_metal(i)) or   // v0.2b
         (opt_Honmethyl and is_methylC(i)) then
        begin
          if (atom^[i].Hexp > 0) and (opt_stripH = false) then Hstr := '' else
            begin
              if (atom^[i].Htot > 0) then Hstr := 'H';
              if (atom^[i].Htot > 1) then Hstr := Hstr + inttostr (atom^[i].Htot);
              if (atom^[i].tag and (atom^[i].Hexp = atom^[i].Htot)) then Hstr := '';  // v0.1f; avoids duplicate H labels for D and T
            end;
        end;
      Hpos := findHpos(i);   
      if (atom^[i].neighbor_count = 0) and (Hstr <> '') then
        begin
          if is_electroneg(uppercase(el)) and (el <> 'N ') and (el <> 'P ') then Hpos := HPleft;
        end;
      if Hpos = HPright then rstr := Hstr else lstr := Hstr;
      chg := atom^[i].formal_charge;
      rad := atom^[i].radical_type;
      iso := atom^[i].nucleon_number;
      if (chg <> 0) then
        begin
          if (abs(chg) > 1) then chgstr := inttostr(chg);
          if (chg < 0) then chgstr := chgstr + '-' else chgstr := chgstr + '+';
        end else chgstr := '';
      if (rad = 1) then chgstr := chgstr + ':';
      if (rad = 2) then chgstr := chgstr + '.';
      if (rad = 3) then chgstr := chgstr + '=';
      extrashift := 1;
      if ((chgstr = '+') and (Hpos = HPup) and (atom^[i].Htot > 1)) then extrashift := 1.4;
      //if ((chgstr = '-') and (Hpos = HPup) and (atom^[i].Htot > 1)) then extrashift := 1.2;
      if ((chgstr = '-') and (Hpos = HPup) and (atom^[i].Htot > 1)) then extrashift := 1.4;
      if (iso > 0) then isostr := inttostr(iso);

      // check for eclipsed atoms;  v0.1f
      for j := 1 to n_atoms do
        begin
          if (j <> i) and (atom^[i].x = atom^[j].x) and (atom^[i].y = atom^[j].y) then
            begin
              atom^[j].hidden := true;
            end;
        end;

      if (alias <> '') then   // v0.2b
        begin
          instr := '  ';
          Hstr := '';
          chgstr := '';
          isostr := '';
        end;

      if (atom^[i].hidden = false) and (sg = false) and (alias = '') then
        begin
          anchor := instr[1];  // what about selenophene?
          outstr := instr;
          writeout('%d dot %d dot moveto',[outXint,outYint]);
          writeouts('('+outstr+') stringwidth pop');
          writeouts('2 div neg 0 rmoveto');
          updatebb(outXint, outYint);
          writeouts('CFont');
          if opt_color then writeouts(rgbstr+' setrgbcolor');
          // place isotope label here
          if (isostr <> '') then
            begin
              writeout('%d dot %d dot moveto',[outXint,(charY+(charY-outYint))]);
              writeouts('('+outstr+') stringwidth pop');
              writeouts('2 div neg 0 rmoveto');
              writeouts('CFontSub');
              writeouts('('+isostr+') stringwidth pop neg 0 rmoveto');
              writeouts('('+isostr+') show');
              writeouts('CFont');
            end;
          //return to initial position            
          writeout('%d dot %d dot moveto',[outXint,outYint]);
          writeouts('('+outstr+') stringwidth pop');
          writeouts('2 div neg 0 rmoveto');
          updatebb(outXint, outYint);
          for j := 1 to strlen do
            begin
              outchar := instr[(j)];
              if (pos(outchar,'0123456789') > 0) then
                begin
                  writeouts('0 fs1 1.4 div neg dot rmoveto CFontSub');
                  writeouts('('+outchar+') show');
                  writeouts('0 fs1 1.4 div dot rmoveto CFont');
                end else
                begin
                  outstr := outchar;
                  writeouts('('+outstr+') show');
                end;
            end;
          strlen := length(rstr);
          if strlen > 0 then
            begin
              for j := 1 to strlen do
                begin
                  outchar := rstr[(j)];
                  if pos(outchar,'0123456789+-') > 0 then
                    begin
                      if pos(outchar,'0123456789') > 0 then
                        begin
                          writeouts('0 fs2 neg dot rmoveto CFontSub');
                          writeouts('('+outchar+') show');
                          writeouts('0 fs2 dot rmoveto CFont');
                        end; 
                    end else
                    begin
                      outstr := outchar;
                      writeouts('('+outstr+') show');
                    end;
                end;
            end;
          // and now the charges
          strlen := length(chgstr);
          if strlen > 0 then
            begin
              for j := 1 to strlen do
                begin
                  outchar := chgstr[(j)];
                  if pos(outchar,'0123456789+-:.=') > 0 then
                    begin
                      if pos(outchar,'0123456789+') > 0 then
                        begin
                          writeouts('CFont 0 fs1 1.8 mul dot rmoveto');
                          if outchar <> '+' then writeouts('CFontSub') else
                            writeouts('CFontChg');
                          writeouts('('+outchar+') show');
                          writeouts('CFont');
                          writeouts('0 fs1 1.8 mul neg dot rmoveto');
                        end;
                      if (outchar='-') then
                        begin
                          writeouts('CFont 0 fs1 1.8 mul dot rmoveto');
                          writeouts('CFontChg Minus');
                          writeouts('CFont');
                          writeouts('0 fs1 1.8 mul neg dot rmoveto');
                        end;  
                      if (outchar=':') then
                        begin
                          writeouts('CFont 0 fs1 1.8 mul dot rmoveto');
                          writeouts('CFontChg Rad1');
                          writeouts('CFont');
                          writeouts('0 fs1 1.8 mul neg dot rmoveto');
                        end;  
                      if (outchar='.') then
                        begin
                          writeouts('CFont 0 fs1 1.8 mul dot rmoveto');
                          writeouts('CFontChg Rad2');
                          writeouts('CFont');
                          writeouts('0 fs1 1.8 mul neg dot rmoveto');
                        end;  
                      if (outchar='=') then
                        begin
                          writeouts('CFont 0 fs1 1.8 mul dot rmoveto');
                          writeouts('CFontChg Rad3');
                          writeouts('CFont');
                          writeouts('0 fs1 1.8 mul neg dot rmoveto');
                        end;  
                    end;
                end;
            end;
        end;
      if (atom^[i].hidden and (atom^[i].element <> 'H ')) or (alias <> '') then   // v0.1f, v0.2b
        begin
          lstr := '';
          rstr := '';
        end;
      if ((lstr <> '') and (Hpos = HPleft)) and (sg = false) then  // right-justified
        begin
          instr := lstr;
          strlen := length(lstr);
          anchor := instr[(strlen)];
          outstr := anchor;
          writeout('%d dot %d dot moveto',[outXint,outYint]);
          writeouts('('+lblstr+') stringwidth pop');
          writeouts('2 div neg 0 rmoveto');
          updatebb(outXint, outYint);
          checkstr1 := '';
          checkstr2 := '';
          check1len := 0;
          check2len := 0;
          for j := 1 to strlen Do
            begin
              outchar := instr[(j)];
              if pos(outchar,'0123456789') > 0 then
                begin
                  checkstr2 := checkstr2 + outchar;
                  inc(check2len);
                end else
                begin
                  outstr := outchar;
                  checkstr1 := checkstr1 + outstr;
                  inc(check1len);
                end;
            end;
          if (check2len > 0) then
            begin
              writeouts('CFontSub');
              writeouts('('+checkstr2+') stringwidth pop');
              writeouts('neg 0 rmoveto');
            end;
          if (check1len > 0) then
            begin
              writeouts('CFont');
              writeouts('('+checkstr1+') stringwidth pop');
              writeouts('neg 0 rmoveto');
            end;
          if (isostr <> '') then
            begin
              writeouts('CFontSub');
              writeouts('('+isostr+') stringwidth pop');
              writeouts('2 div neg 0 rmoveto');
              writeouts('CFont');
            end;
          for j := 1 to strlen do
            begin
              outchar := instr[(j)];
              if (pos(outchar,'0123456789') > 0) then
                begin
                  writeouts('0 fs2 neg dot rmoveto CFontSub');
                  writeouts('('+outchar+') show');
                  writeouts('0 fs2 dot rmoveto CFontSub');
                end else
                begin
                  outstr := outchar;
                  writeouts('('+outstr+') show');
                end;
            end;
        end;
      if ((lstr <> '') and (Hpos = HPup)) and (sg = false) then 
        begin
          anchor := el[1];
          outstr := anchor;
          writeout('%d dot %d dot moveto',[outXint,outYint]);
          writeouts('('+outstr+') stringwidth pop');
          writeout('2 div neg %d rmoveto',[round(fontsize1*0.8*extrashift)]);
          updatebb(outXint, outYint);
          instr := lstr;
          strlen := length(lstr);
          if (strlen > 0) then
            begin
              for j := 1 to strlen do
                begin
                  outchar := lstr[(j)];
                  if (pos(outchar,'0123456789') > 0) then
                    begin
                      if (pos(outchar,'0123456789') > 0) then
                        begin
                          writeouts('0 fs2 neg dot rmoveto CFontSub');
                          writeouts('('+outchar+') show');
                          writeouts('0 fs2 dot rmoveto CFontSub');
                        end; 
                    end else
                    begin
                      outstr := outchar;
                      writeouts('('+outstr+') show');
                    end;
                end;
            end;
        end;
      if ((lstr <> '') and (Hpos = HPdown)) and (sg = false) then 
        begin
          anchor := el[1];
          outstr := anchor;
          writeout('%d dot %d dot moveto',[outXint,outYint]);
          writeouts('('+outstr+') stringwidth pop');
          writeout('2 div neg %d neg rmoveto',[round(fontsize1*0.85)]);
          updatebb(outXint, outYint);
          instr := lstr;
          strlen := length(lstr);
          if (strlen > 0) then
            begin
              for j := 1 to strlen Do
                begin
                  outchar := lstr[(j)];
                  if (pos(outchar,'0123456789') > 0) then
                    begin
                      if (pos(outchar,'0123456789') > 0) then
                        begin
                          writeouts('0 fs2 neg dot rmoveto CFontSub');
                          writeouts('('+outchar+') show');
                          writeouts('0 fs2 dot rmoveto CFontSub');
                        end; 
                    end else
                    begin
                      outstr := outchar;
                      writeouts('('+outstr+') show');
                    end;
                end;
            end;
        end;
    end;
  if opt_atomnum then
    begin
      writeouts('CFontNum');
      writeouts('0.7 setgray');
      for i := 1 to n_atoms do
        begin
          sg := false;
          if opt_sgroups then sg := atom^[i].sg;  // v0.2a
          el := atom^[i].element;
          if ((not ((el = 'H ') and opt_stripH)) or (atom^[i].hidden = false)) and (sg = false) then
            begin
              charX := round((atom^[i].x+xoffset)*blfactor);
              charY := round((atom^[i].y+yoffset)*blfactor);
              outXint := charX;
              //outYint := charY - round(fontsize1*0.5);  // was: 20
              outYint := charY;
              updatebb(outXint, outYint);
              outstr := inttostr(i);
              writeout('%d dot %d dot moveto',[outXint,outYint]);
              writeouts('('+outstr+') show');
            end;
        end;
      //writeln('0.0 setgray');
      writeouts('0.0 setgray');
    end;  
  if (opt_bondnum and (n_bonds > 0)) then
    begin
      writeouts('CFontNum');
      writeouts('0.7 setgray');
      for i := 1 to n_bonds do
        begin
          sg := false;
          if opt_sgroups then sg := bond^[i].sg;  // v0.2a
          if (not bond^[i].hidden) and (sg = false) then
            begin
              a1 := bond^[i].a1;
              a2 := bond^[i].a2;
              charX := round(((atom^[a1].x + atom^[a2].x)/2 +xoffset)*blfactor);
              charY := round(((atom^[a1].y + atom^[a2].y)/2 +yoffset)*blfactor);
              outXint := charX;
              outYint := charY - round(fontsize1*0.5);  // was: 20
              updatebb(outXint, outYint);
              outstr := inttostr(i);
              writeout('%d dot %d dot moveto',[outXint,outYint]);
              writeouts('('+outstr+') stringwidth pop');
              writeouts('2 div neg 0 rmoveto');
              writeouts('('+outstr+') show');
            end;
        end;     
      writeouts('0.0 setgray');
    end;
  if opt_maps then  // v0.3a
    begin
      writeouts('CFontSub');
      writeouts('1.0 0 0.2 setrgbcolor');
      for i := 1 to n_atoms do
        begin
          if (atom^[i].map_id <> 0) then
            begin
              charX := round((atom^[i].x+xoffset)*blfactor  + 1.5*fontsize1);
              charY := round((atom^[i].y+yoffset)*blfactor);
              outXint := charX;
              //outYint := charY - round(fontsize1*0.5);  // was: 20
              outYint := charY;
              updatebb(outXint, outYint);
              outstr := '.' + inttostr(atom^[i].map_id) + '.';
              writeout('%d dot %d dot moveto',[outXint,outYint]);
              writeouts('('+outstr+') show');
            end;
        end;
      writeouts('0 0 0 setrgbcolor');
    end;  
end;

procedure printSVGchars;
const
  HPright = 1;
  HPleft  = 2;
  HPup    = 3;
  HPdown  = 4;
  kerning1 : string = ' dx="-5"';
var
  i, j, k : integer;
  instr : string[20];
  checkstr1, checkstr2 : string[64];
  check1len, check2len : integer;
  outX, outY : single;
  strlen : integer;
  outstr : str4;
  outchar, anchor : char;
  charX, charY : single;
  el : str2;
  tmpstr : string;
  Hpos : integer;
  Hstr : string;
  rstr, lstr : string;
  chg : integer;
  chgstr : string;
  rad : integer;
  iso : integer;
  isostr : string;
  lblstr : string;
  extrashift : double;
  rgbstr : string;
  a1, a2 : integer;
  dysub : single;
  dysuper : single;
  sg : boolean;  
  ylevel, prev_ylevel, delta_y : single;
  fs4 : integer;
  colstr : string;
  alias : string;  // v0.2b  
  strwidth : single;  // v0.2c
  bstr : string;
  kernstr : string;
begin
  dysub := (fontsize2*0.5);
  dysuper := -(fontsize2*0.7);
  for i := 1 to n_atoms Do
    begin
      ylevel := 0;  
      sg := false;
      if opt_sgroups then sg := atom^[i].sg;
      alias := atom^[i].alias;   // v0.2b
      outstr := '    ';
      Hstr := '';
      rstr := '';
      lstr := '';
      chg := 0;
      chgstr := '';
      isostr := '';
      el := atom^[i].element;
      tmpstr := lowercase(el);
      tmpstr[1] := upcase(tmpstr[1]);
      instr := tmpstr;
      if (instr[2] = ' ') then delete(instr,2,1);
      colstr := '';
      if opt_color then 
        begin
          rgbstr := lookuprgbhex(instr);
          if (el = 'H ') then
            begin
              if (atom^[i].nucleon_number = 2) then rgbstr := lookuprgbhex('D');
              if (atom^[i].nucleon_number = 3) then rgbstr := lookuprgbhex('T');
            end;
          colstr := ' fill="#'+rgbstr+'"';
        end;
      lblstr := instr;
      {$IFDEF debug}
      debugoutput('atom '+inttostr(i)+': Hexp = '+inttostr(atom^[i].Hexp)+' Htot = '+ inttostr(atom^[i].Htot));  // v0.1f
      {$ENDIF}
      charX := (atom^[i].x+xoffset)*blfactor*svg_factor;
      charY := (atom^[i].y+yoffset)*blfactor*-svg_factor + svg_yoffset;
      outX := charX  - (fontsize1*0.35);
      outY := charY + (fontsize1*0.4);
      strlen := length(instr);
      Hpos := HPright;  // default
      if (opt_Honhetero and is_electroneg(uppercase(el))) or
         (opt_Honhetero and is_metal(i)) or   // v0.2b
         (opt_Honmethyl and is_methylC(i)) then
        begin
          if (atom^[i].Hexp > 0) and (opt_stripH = false) then Hstr := '' else
            begin
              if (atom^[i].Htot > 0) then Hstr := 'H';
              if (atom^[i].Htot > 1) then Hstr := Hstr + inttostr (atom^[i].Htot);
              if (atom^[i].tag and (atom^[i].Hexp = atom^[i].Htot)) then Hstr := '';  // v0.1f; avoids duplicate H labels for D and T
            end;
        end;
      Hpos := findHpos(i);   
      if (atom^[i].neighbor_count = 0) and (Hstr <> '') then
        begin
          if is_electroneg(uppercase(el)) and (el <> 'N ') and (el <> 'P ') then Hpos := HPleft;
        end;
      if Hpos = HPright then rstr := Hstr else lstr := Hstr;
      chg := atom^[i].formal_charge;
      rad := atom^[i].radical_type;
      iso := atom^[i].nucleon_number;
      if (chg <> 0) then
        begin
          if (abs(chg) > 1) then chgstr := inttostr(chg);
          if (chg < 0) then chgstr := chgstr + '-' else chgstr := chgstr + '+';
        end else chgstr := '';
      if (rad = 1) then chgstr := chgstr + ':';
      if (rad = 2) then chgstr := chgstr + '.';
      if (rad = 3) then chgstr := chgstr + '=';
      extrashift := 1;
      if ((chgstr = '+') and (Hpos = HPup) and (atom^[i].Htot > 1)) then extrashift := 1.4;
      if ((chgstr = '-') and (Hpos = HPup) and (atom^[i].Htot > 1)) then extrashift := 1.4;
      if (iso > 0) then isostr := inttostr(iso) else isostr := '';

      // check for eclipsed atoms;  v0.1f
      for j := 1 to n_atoms do
        begin
          if (j <> i) and (atom^[i].x = atom^[j].x) and (atom^[i].y = atom^[j].y) then
            begin
              atom^[j].hidden := true;
            end;
        end;

      if (alias <> '') then   // v0.2b
        begin
          instr := '  ';
          Hstr := '';
          chgstr := '';
          isostr := '';
        end;

      if (atom^[i].hidden = false) and (sg = false) and (alias = '') then
        begin
          bstr := '';
          anchor := instr[1];  // what about selenophene?
          outstr := instr;
          strwidth := length(rstr)*fontsize1*1.1;  
          //chk_svg_max_xy((outX+0.1*get_stringwidth(fontsize1,rstr)),outY);
          chk_svg_max_xy((outX+strwidth),outY);
          bstr := format('<text x="%1.1f" y="%1.1f">',[outx,outy],fsettings);

          for j := 1 to strlen do
            begin
              prev_ylevel := ylevel;
              outchar := instr[(j)];
              if (pos(outchar,'0123456789') > 0) then
                begin
                  ylevel := dysub;
                  delta_y := ylevel - prev_ylevel;
                  if (abs(delta_y) > 0.1) then
                    bstr := bstr + format('<tspan font-size="%d" dy="%1.1f">%s</tspan>',[fontsize2,delta_y,outchar],fsettings)
                  else
                    bstr := bstr + format('<tspan font-size="%d">%s</tspan>',[fontsize2,outchar],fsettings);
                end else
                begin
                  outstr := outchar;
                  ylevel := 0;
                  delta_y := ylevel - prev_ylevel;
                  if (abs(delta_y) > 0.1) then
                    bstr := bstr + format('<tspan%s dy="%1.1f">%s</tspan>',[colstr,delta_y,outchar],fsettings)
                  else
                    bstr := bstr + format('<tspan%s>%s</tspan>',[colstr,outstr],fsettings);
                end;
            end;
          strlen := length(rstr);
          if strlen > 0 then
            begin
              for j := 1 to strlen do
                begin
                  prev_ylevel := ylevel;
                  outchar := rstr[(j)];
                  if pos(outchar,'0123456789+-') > 0 then
                    begin
                      if pos(outchar,'0123456789') > 0 then
                        begin
                          ylevel := dysub;
                          delta_y := ylevel - prev_ylevel;
                          if (abs(delta_y) > 0.1) then
                            bstr := bstr + format('<tspan font-size="%d" dy="%1.1f">%s</tspan>',[fontsize2,delta_y,outchar],fsettings)
                          else
                            bstr := bstr + format('<tspan font-size="%d">%s</tspan>',[fontsize2,outchar],fsettings);
                        end; 
                    end else
                    begin
                      outstr := outchar;
                      ylevel := 0;
                      delta_y := ylevel - prev_ylevel;
                      if (abs(delta_y) > 0.1) then
                        bstr := bstr + format('<tspan dy="%1.1f">%s</tspan>',[delta_y,outstr],fsettings)
                      else
                        bstr := bstr + format('<tspan>%s</tspan>',[outstr],fsettings)
                    end;
                end;
            end;
          // and now the charges
          strlen := length(chgstr);
          prev_ylevel := ylevel;
          if strlen > 0 then
            begin
              for j := 1 to strlen do
                begin
                  outchar := chgstr[(j)];
                  prev_ylevel := ylevel;
                  if pos(outchar,'0123456789+-:.=') > 0 then
                    begin
                      if pos(outchar,'0123456789+') > 0 then
                        begin
                          ylevel := dysuper;
                          delta_y := ylevel - prev_ylevel;
                          if (abs(delta_y) > 0.1) then
                            bstr := bstr + format('<tspan font-size="%d" dy="%1.1f">%s</tspan>',[fontsize2,delta_y,outchar],fsettings)
                          else
                            bstr := bstr + format('<tspan font-size="%d">%s</tspan>',[fontsize2,outchar],fsettings)
                        end;
                      if (outchar='-') then
                        begin
                          ylevel := dysuper;
                          delta_y := ylevel - prev_ylevel;
                          if (abs(delta_y) > 0.1) then
                            bstr := bstr + format('<tspan dy="%1.1f">%s</tspan>',[delta_y,outchar],fsettings)
                          else
                            bstr := bstr + format('<tspan>%s</tspan>',[outchar],fsettings);
                        end;  
                      if (outchar=':') then
                        begin
                          ylevel := -1;
                          delta_y := ylevel - prev_ylevel;
                          if (abs(delta_y) > 0.1) then
                            bstr := bstr + format('<tspan font-weight="bold" dy="%1.1f">%s</tspan>',[delta_y,outchar],fsettings)
                          else
                            bstr := bstr + format('<tspan font-weight="bold">%s</tspan>',[outchar],fsettings);
                        end;  
                      if (outchar='.') then
                        begin
                          ylevel := dysuper;
                          delta_y := ylevel - prev_ylevel;
                          if (abs(delta_y) > 0.1) then
                            bstr := bstr + format('<tspan dy="%1.1f">&#8226;</tspan>',[delta_y],fsettings)
                          else
                            bstr := bstr + '<tspan>&#8226;</tspan>';
                        end;  
                      if (outchar='=') then
                        begin
                          ylevel := dysuper;
                          delta_y := ylevel - prev_ylevel;
                          if (abs(delta_y) > 0.1) then
                            bstr := bstr + format('<tspan dy="%1.1f">^^</tspan>',[delta_y],fsettings)
                          else
                            bstr := bstr + '<tspan>^^</tspan>';
                        end;  
                    end;
                end;
            end;
          writeouts(bstr); bstr := '';
          writeouts('</text>');
        end;
      if (atom^[i].hidden and (atom^[i].element <> 'H ')) or (alias <> '') then   // v0.1f, v0.2b
        begin
          lstr := '';
          rstr := '';
        end;
        
      if (((lstr <> '') and (Hpos = HPleft)) or (isostr <> '')) and (sg = false) then  // right-justified
        begin
          bstr := '';
          ylevel := 0;
          instr := lstr;
          strlen := length(lstr);
          anchor := instr[(strlen)];
          outstr := anchor;
          bstr := format('<text text-anchor="end" x="%1.1f" y="%1.1f">',[outx,outy],fsettings);

          if ((lstr <> '') and (Hpos = HPleft)) then
            begin
              checkstr1 := '';
              checkstr2 := '';
              check1len := 0;
              check2len := 0;
              for j := 1 to strlen do
                begin
                  prev_ylevel := ylevel;
                  outchar := instr[(j)];
                  if (pos(outchar,'0123456789') > 0) then
                    begin
                      ylevel := dysub;
                      delta_y := ylevel - prev_ylevel;
                      if (abs(delta_y) > 0.1) then
                        bstr := bstr + format('<tspan font-size="%d" dy="%1.1f">%s</tspan>',[fontsize2,delta_y,outchar],fsettings)
                      else
                        bstr := bstr + format('<tspan font-size="%d">%s</tspan>',[fontsize2,outchar],fsettings);
                    end else
                    begin
                      ylevel := 0;
                      delta_y := ylevel - prev_ylevel;
                      if (abs(delta_y) > 0.1) then
                        bstr := bstr + format('<tspan dy="%1.1f">%s</tspan>',[delta_y,outchar],fsettings)
                      else
                        bstr := bstr + format('<tspan>%s</tspan>',[outchar],fsettings);
                    end;
                end;
            end;  // lstr <> ''
          if (isostr <> '') then
            begin
              prev_ylevel := ylevel;
              ylevel := dysuper;
              delta_y := ylevel - prev_ylevel;
              if (abs(delta_y) > abs(dysuper)) then kernstr := format(' dx="-%d"',[(fontsize2 div 2)],fsettings) else kernstr := '';
              //bstr := bstr + format('<text text-anchor="end" x="%1.1f" y="%1.1f">',[outx,outy],fsettings);
              bstr := bstr + format('<tspan font-size="%d" dy="%1.1f"%s>%s</tspan>',[fontsize2,delta_y,kernstr,isostr],fsettings);
              ylevel := dysuper;
            end;
          bstr := bstr + '</text>';
          writeouts(bstr); bstr := '';
        end;

      if ((lstr <> '') and (Hpos = HPup)) and (sg = false) then 
        begin
          anchor := el[1];
          outstr := anchor;
          writeout('<text x="%1.1f" y="%1.1f">',[outx,(outy-fontsize1*0.85*extrashift)]);
          instr := lstr;
          strlen := length(lstr);
          bstr := '';
          if (strlen > 0) then
            begin
              for j := 1 to strlen do
                begin
                  outchar := lstr[(j)];
                  if (pos(outchar,'0123456789') > 0) then
                    begin
                      if (pos(outchar,'0123456789') > 0) then
                        begin
                          bstr := bstr + format('<tspan font-size="%d" dy="%1.1f">%s</tspan>',[fontsize2,dysub,outchar],fsettings);
                        end; 
                    end else
                    begin
                      outstr := outchar;
                      bstr := bstr + format('<tspan>%s</tspan>',[outchar],fsettings);
                    end;
                end;
            end;
          bstr := bstr + '</text>';
          writeouts(bstr); bstr := '';
        end;

      if ((lstr <> '') and (Hpos = HPdown)) and (sg = false) then 
        begin
          anchor := el[1];
          outstr := anchor;
          writeout('<text x="%1.1f" y="%1.1f">',[outx,(outY+fontsize1*0.85)]);
          instr := lstr;
          strlen := length(lstr);
          bstr := '';
          if (strlen > 0) then
            begin
              for j := 1 to strlen do
                begin
                  outchar := lstr[(j)];
                  if (pos(outchar,'0123456789') > 0) then
                    begin
                      if (pos(outchar,'0123456789') > 0) then
                        begin
                          bstr := bstr + format('<tspan font-size="%d" dy="%1.1f">%s</tspan>',[fontsize2,dysub,outchar],fsettings);
                        end; 
                    end else
                    begin
                      outstr := outchar;
                      bstr := bstr + format('<tspan>%s</tspan>',[outchar],fsettings);
                    end;
                end;
            end;
          bstr := bstr + '</text>';
          writeouts(bstr); bstr := '';
        end;
    end;
  if opt_atomnum then
    begin
      fs4 := round(fontsize1 / 2.5);
      for i := 1 to n_atoms do
        begin
          el := atom^[i].element;
          if (not ((el = 'H ') and opt_stripH)) or (atom^[i].hidden = false) then
            begin
              charX := (atom^[i].x+xoffset)*blfactor*svg_factor;
              charY := (atom^[i].y+yoffset)*blfactor*-svg_factor + svg_yoffset;
              outX := charX;
              outY := charY;
              outstr := inttostr(i);
              strwidth := length(outstr)*fs4*1.1;  
              chk_svg_max_xy((outX+strwidth),outY);
              writeout('<text style="fill:#999999; font-size:%d;" x="%1.1f" y="%1.1f">%s</text>',[fs4,outx,outy,outstr]);
            end;
        end;
    end;  
  if (opt_bondnum and (n_bonds > 0)) then
    begin
      fs4 := round(fontsize1 / 2.5);
      for i := 1 to n_bonds do
        begin
          if (not bond^[i].hidden) then
            begin
              a1 := bond^[i].a1;
              a2 := bond^[i].a2;
              charX := ((atom^[a1].x + atom^[a2].x)/2 +xoffset)*blfactor*svg_factor;
              charY := ((atom^[a1].y + atom^[a2].y)/2 +yoffset)*blfactor*-svg_factor + svg_yoffset;
              outX := charX;
              outY := charY + fs4*0.4;  // was: 20
              outstr := inttostr(i);
              writeout('<text style="fill:#999999; font-size:%d;" text-anchor="middle" x="%1.1f" y="%1.1f">%s</text>',[fs4,outx,outy,outstr]);
            end;
        end;     
    end;
  if opt_maps then  // v0.3a
    begin
      fs4 := round(fontsize1 / 1.25);
      for i := 1 to n_atoms do
        begin
          if (atom^[i].map_id <> 0) then
            begin
              charX := (atom^[i].x+xoffset)*blfactor*svg_factor + 0.125*fontsize1;
              charY := (atom^[i].y+yoffset)*blfactor*-svg_factor + svg_yoffset;
              outX := charX;
              outY := charY;
              outstr := '.' + inttostr(atom^[i].map_id) + '.';
              strwidth := length(outstr)*fs4*1.1;  
              chk_svg_max_xy((outX+strwidth),outY);
              writeout('<text fill="#ff0033" style="font-size:%dpx"  font-weight="bold" x="%1.1f" y="%1.1f">%s</text>',[fs4,outx,outy,outstr]);
            end;
        end;
    end;  
end;

function popchar1(var instr:string):string;  // v0.2b
var
  i : integer;
  outstr : string;
  codechar : char;
begin
  outstr := '';
  if (length(instr) > 0) then
    begin
      codechar := ' ';
      if (instr[1] = '\') then
        begin
          delete(instr,1,1);
          if (length(instr) > 0) then
            begin
              if (instr[1] = 'n') then
                begin
                  codechar := '=';
                  delete(instr,1,1);
                end;
              if (instr[1] = 'S') then
                begin
                  codechar := '+';
                  delete(instr,1,1);
                end;
              if (instr[1] = 's') then
                begin
                  codechar := '-';
                  delete(instr,1,1);
                end;
            end;
        end;
      outstr := codechar + instr[1];
      delete(instr,1,1);
    end;
  popchar1 := outstr;
end;

procedure printPSlabel_autosub(x:single;y:single;outstr:string;just:char);
var
  i,j,p : integer;
  charX,charY : integer;
  outXint,outYint : integer;
  instr : string;
  strlen : integer;
  nstr, tstr : string;
  outchar : char;
  smode : smallint;  // v0.2b 1 = superscript, 0 = normal, -1 = autosub (digits only) -2 = sub
  tmpstr, tmpstr_out, tmpstr_n, tmpstr_s : string;  // v0.2b
  attr : attr_arr;
  trimstr_n, trimstr_s, anchorstr_n, anchorstr_s, purestr : string;
  anchorpos : integer;
  n_mode : boolean;
  sub_mode : boolean;
  sup_mode : boolean;
  b : byte;
  trstr : string;
begin
  if (outstr = '') then exit;
  smode := -1;
  tmpstr := outstr;  // v0.2b
  tmpstr_out := '';
  tmpstr_n := '';
  tmpstr_s := '';
  charX := round((x+xoffset)*blfactor);
  charY := round((y+yoffset)*blfactor);
  outXint := charX;
  outYint := charY - round(fontsize1*1.5);  // was: 20  / 0.8
  writeout('%d dot %d dot moveto',[outXint,outYint]);
  updatebb(outXint, outYint);
  // new label processing
  fillchar(attr,sizeof(attr),0);
  anchorpos := 0;
  purestr := '';
  trimstr_n := '';
  trimstr_s := '';
  tmpstr := outstr;
  n_mode := false;
  sub_mode := false;
  sup_mode := false;
  while (length(tmpstr) > 0) do
    begin
      p := length(purestr);
      if (tmpstr[1] = '^') then
        begin
          delete(tmpstr,1,1);
          if (anchorpos = 0) then anchorpos := p + 1;  // accept only the first ^
        end;
      if (tmpstr[1] = '\') then
        begin
          delete(tmpstr,1,1);
          if (tmpstr[1] = 'n') then
            begin
              delete(tmpstr,1,1);
              n_mode := (not n_mode);
            end;
          if (tmpstr[1] = 's') then
            begin
              delete(tmpstr,1,1);
              sub_mode := (not sub_mode);
            end;
          if (tmpstr[1] = 'S') then
            begin
              delete(tmpstr,1,1);
              sup_mode := (not sup_mode);
            end;
        end;
      purestr := purestr + tmpstr[1];
      delete(tmpstr,1,1);
      p := length(purestr);
      if n_mode then
        attr[p] := 0
      else
        begin
          if sup_mode then
            attr[p] := 3
          else
            begin
              if sub_mode then
                attr[p] := 1
              else
                begin
                  outchar := purestr[p];
                  if (pos(outchar,'0123456789') > 0) then attr[p] := 1 else attr[p] := 0;
                end;
            end;
        end;
    end;
  // now we have the pure string together with the attributes in attr[]
  if (anchorpos > length(purestr)) then purestr := purestr + ' ';  // like ISIS/Draw
  if (anchorpos = 0) then
    begin
      if (just = 'L') then anchorpos := 1;
      if (just = 'R') then anchorpos := length(purestr);
      if (just = 'C') then anchorpos := length(purestr) + 1;  // sic!
    end;
  // now assemble the two trim strings for normal and small font
  if (anchorpos > 1) then
    begin
      trimstr_n := '';
      trimstr_s := '';
      for i := 1 to (anchorpos - 1) do
        begin
          if odd(attr[i]) then trimstr_s := trimstr_s + purestr[i] else
                               trimstr_n := trimstr_n + purestr[i];
        end;
      end;
  if (purestr = '') then exit;  // just to be sure

  {$IFDEF debug}
  debugoutput('anchor position: '+inttostr(anchorpos));  // v0.2b
  debugoutput('trim string (n): '+trimstr_n);  // v0.2b
  debugoutput('trim string (s): '+trimstr_s);  // v0.2b
  {$ENDIF}

  // and now the (relative) positioning
  if (just = 'C') then
    begin
      if (length(trimstr_n) > 0) then
        begin
          (*
          writeln('CFont');
          writeln('(',trimstr_n,') stringwidth pop');
          writeln('2 div neg 0 rmoveto');
          *)
          writeouts('CFont');
          writeouts('('+trimstr_n+') stringwidth pop');
          writeouts('2 div neg 0 rmoveto');
          updateBB(outXint+round(1.0*get_stringwidth(fontsize1,trimstr_n)),outYint);
        end;
      if (length(trimstr_s) > 0) then
        begin
          (*
          writeln('CFontSub');
          writeln('(',trimstr_s,') stringwidth pop');
          writeln('2 div neg 0 rmoveto');
          *)
          writeouts('CFontSub');
          writeouts('('+trimstr_s+') stringwidth pop');
          writeouts('2 div neg 0 rmoveto');
          updateBB(outXint+round(1.0*get_stringwidth(fontsize2,trimstr_s)),outYint);
        end;
    end else
    begin
      // first, handle the anchor character
      p := anchorpos;
      outchar := purestr[p];
      if (odd(attr[p])) then
        begin   // small font
          (*
          writeln('CFontSub');
          writeln('(',outchar,') stringwidth pop');
          writeln('2 div neg 0 rmoveto');
          *)
          writeouts('CFontSub');
          writeouts('('+outchar+') stringwidth pop');
          writeouts('2 div neg 0 rmoveto');
        end else
        begin   // normal font
          (*
          writeln('CFont');
          writeln('(',outchar,') stringwidth pop');
          writeln('2 div neg 0 rmoveto');
          *)
          writeouts('CFont');
          writeouts('('+outchar+') stringwidth pop');
          writeouts('2 div neg 0 rmoveto');
        end;
      if (length(trimstr_n) > 0) then
        begin
          (*
          writeln('CFont');
          writeln('(',trimstr_n,') stringwidth pop neg 0 rmoveto');
          *)
          writeouts('CFont');
          writeouts('('+trimstr_n+') stringwidth pop neg 0 rmoveto');
          updateBB(outXint-round(2.0*get_stringwidth(fontsize1,trimstr_n)),outYint);
        end;
      if (length(trimstr_s) > 0) then
        begin
          (*
          writeln('CFontSub');
          writeln('(',trimstr_s,') stringwidth pop neg 0 rmoveto');
          *)
          writeouts('CFontSub');
          writeouts('('+trimstr_s+') stringwidth pop neg 0 rmoveto');
          updateBB(outXint-round(2.0*get_stringwidth(fontsize2,trimstr_s)),outYint);
        end;
    end;
  updateBB(outXint+round(2.0*get_stringwidth(fontsize1,purestr)),outYint);    
  // now positioning is finished and we can print the string character by character
  for i := 1 to length(purestr) do
    begin
      outchar := purestr[i];
      trstr := outchar;  // if necessary, do some translations here
      b := attr[i];
      if odd(b) then
        begin
          if (b = 1) then   // subscript
            begin
              (*
              writeln('0 fs1 1.4 div neg dot rmoveto CFontSub');
              writeln('(',trstr,') show');
              writeln('0 fs1 1.4 div dot rmoveto CFont');
              *)
              writeouts('0 fs1 1.4 div neg dot rmoveto CFontSub');
              writeouts('('+trstr+') show');
              writeouts('0 fs1 1.4 div dot rmoveto CFont');
            end;
          if (b = 3) then   // subscript
            begin
              (*
              writeln('0 fs1 0.6 div dot rmoveto CFontSub');
              writeln('(',trstr,') show');
              writeln('0 fs1 0.6 div neg dot rmoveto CFont');
              *)
              writeouts('0 fs1 0.6 div dot rmoveto CFontSub');
              writeouts('('+trstr+') show');
              writeouts('0 fs1 0.6 div neg dot rmoveto CFont');
            end;
        end else
        begin
          writeouts('CFont ('+trstr+') show');
        end;
    end;   // for i
  //if opt_color then writeln('0 0 0 setrgbcolor');
end;

procedure printSVGlabel_autosub(x:single;y:single;outstr:string;just:char);
var
  i, j, p : integer;
  charX,charY : single;
  outX,outY : single;
  outchar : char;
  dysub : single;
  ylevel, prev_ylevel, delta_y : single;
  smode : smallint;  // v0.2b 1 = superscript, 0 = normal, -1 = autosub (digits only) -2 = sub
  tmpstr, tmpstr_out, tmpstr_n, tmpstr_s : string;  // v0.2b
  attr : attr_arr;
  trimstr_n, trimstr_s, anchorstr_n, anchorstr_s, purestr : string;
  anchorpos : integer;
  n_mode : boolean;
  sub_mode : boolean;
  sup_mode : boolean;
  b, prev_b : byte;
  trstr : string;
  cwf : single;  // character width factor
  strwidth : single;  // v0.2c
  bstr : string;  // v0.4
begin
  if (outstr = '') then exit;
  smode := -1;
  tmpstr := outstr;  // v0.2b
  tmpstr_out := '';
  tmpstr_n := '';
  tmpstr_s := '';
  dysub := (fontsize2*0.7);
  charX := (x+xoffset)*blfactor*svg_factor;
  charY := (y+yoffset)*blfactor*-svg_factor + svg_yoffset;
  cwf := 0.35;  // default character width

  // new label processing, v0.2b
  fillchar(attr,sizeof(attr),0);
  anchorpos := 0;
  purestr := '';
  trimstr_n := '';
  trimstr_s := '';
  tmpstr := outstr;
  n_mode := false;
  sub_mode := false;
  sup_mode := false;
  while (length(tmpstr) > 0) do
    begin
      p := length(purestr);
      if (tmpstr[1] = '^') then
        begin
          delete(tmpstr,1,1);
          if (anchorpos = 0) then anchorpos := p + 1;  // accept only the first ^
        end;
      if (tmpstr[1] = '\') then
        begin
          delete(tmpstr,1,1);
          if (tmpstr[1] = 'n') then
            begin
              delete(tmpstr,1,1);
              n_mode := (not n_mode);
            end;
          if (tmpstr[1] = 's') then
            begin
              delete(tmpstr,1,1);
              sub_mode := (not sub_mode);
            end;
          if (tmpstr[1] = 'S') then
            begin
              delete(tmpstr,1,1);
              sup_mode := (not sup_mode);
            end;
        end;
      purestr := purestr + tmpstr[1];
      delete(tmpstr,1,1);
      p := length(purestr);
      if n_mode then
        attr[p] := 0
      else
        begin
          if sup_mode then
            attr[p] := 3
          else
            begin
              if sub_mode then
                attr[p] := 1
              else
                begin
                  outchar := purestr[p];
                  if (pos(outchar,'0123456789') > 0) then attr[p] := 1 else attr[p] := 0;
                end;
            end;
        end;
    end;
  // now we have the pure string together with the attributes in attr[]
  if (anchorpos > length(purestr)) then purestr := purestr + ' ';  // like ISIS/Draw
  if (anchorpos = 0) then
    begin
      if (just = 'L') then anchorpos := 1;
      if (just = 'R') then anchorpos := length(purestr);
      if (just = 'C') then anchorpos := 1;  // sic!
    end;
  if (purestr = '') then exit;  // just to be sure  
  {$IFDEF debug}
  debugoutput('anchor position: '+inttostr(anchorpos));
  {$ENDIF}

  // and now the (relative) positioning
  tmpstr := '';
  if (anchorpos > 1) then tmpstr := ' text-anchor="end" ';
  if (just = 'C') then
    begin
      outX := charX;
      outY := charY + (fontsize1*0.38);
      strwidth := length(purestr)*0.5*fontsize1*1.1;  
      //chk_svg_max_xy((outX+strwidth),outY);
      chk_svg_max_xy((outX+0.6*0.5*get_stringwidth(fontsize1,purestr)),outY);
      writeout('<text text-anchor="middle" x="%1.1f" y="%1.1f">',[outx,outy]);
    end else
    begin
      // first, handle the anchor character
      p := anchorpos;
      outchar := purestr[p];
      if (pos(outchar,'MW') > 0) then cwf := 0.45;
      if (outchar = '1') then cwf := 0.25;
      if (pos(outchar,'iIl.:,;!') > 0) then cwf := 0.15;
      if (odd(attr[p])) then
        begin   // small font
          outX := charX  - (fontsize2*cwf);
          outY := charY + (fontsize1*0.38);  // sic! (fontsize1)
          writeout('<text font-size="%d" %s x="%1.1f" y="%1.1f">',[fontsize2,tmpstr,outx,outy]);
        end else
        begin   // normal font
          outX := charX  - (fontsize1*cwf);
          outY := charY + (fontsize1*0.38);
          writeout('<text %s x="%1.1f" y="%1.1f">',[tmpstr,outx,outy]);
        end;
      if (just = 'L') then
        begin
          strwidth := length(purestr)*fontsize1*1.1;  
          //chk_svg_max_xy((outX+strwidth),outY);
          chk_svg_max_xy((outX+0.6*get_stringwidth(fontsize1,copy(purestr,anchorpos,(length(purestr)-anchorpos)))),outY);
        end;
    end;
    
  // now positioning is finished and we can print the string character by character;
  ylevel := 0;
  // first, print any character left of the anchor (if any)
  if (anchorpos > 1) then
    begin
      bstr := '';
      prev_b := 255;
      for i := 1 to (anchorpos - 1) do
        begin
          prev_ylevel := ylevel;
          outchar := purestr[i];
          trstr := outchar;  // if necessary, do some translations here
          b := attr[i];
          if (b <> prev_b) and (prev_b <> 255) then 
            bstr := bstr + '</tspan>';
          if odd(b) then
            begin
              if (b = 1) then   // subscript
                begin
                  ylevel := dysub;
                  delta_y := ylevel - prev_ylevel;
                  if (b = prev_b) then 
                    bstr := bstr + trstr
                  else
                    begin
                      if (abs(delta_y) > 0.1) then
                        bstr := bstr + format('<tspan font-size="%d" dy="%1.1f">%s',[fontsize2,delta_y,trstr],fsettings)
                      else
                        bstr := bstr + format('<tspan font-size="%d">%s',[fontsize2,trstr],fsettings);
                    end;
                end;
              if (b = 3) then   // superscript
                begin
                  ylevel := -1.0*dysub;    // may need adjustment!
                  delta_y := ylevel - prev_ylevel;
                  if (b = prev_b) then write(trstr) else
                    begin
                      if (abs(delta_y) > 0.1) then
                        bstr := bstr + format('<tspan font-size="%d" dy="%1.1f">%s',[fontsize2,delta_y,trstr],fsettings)
                      else
                        bstr := bstr + format('<tspan font-size="%d">%s',[fontsize2,trstr],fsettings);
                    end;
                end;
            end else
            begin
              ylevel := 0;
              delta_y := ylevel - prev_ylevel;
              if (b = prev_b) then 
                bstr := bstr + trstr
              else
                begin
                  if (abs(delta_y) > 0.1) then
                    bstr := bstr + format('<tspan dy="%1.1f">%s',[delta_y,trstr],fsettings)
                  else
                    bstr := bstr + format('<tspan font-size="%d">%s',[fontsize1,trstr],fsettings);
                end;
            end;
          prev_b := b;
        end;  // for
      // now, re-establish the original anchor position with left-justified alignment
      outchar := purestr[p];
      if (pos(outchar,'MW') > 0) then cwf := 0.45;
      if (outchar = '1') then cwf := 0.25;
      if (pos(outchar,'iIl.:,;!') > 0) then cwf := 0.15;
      tmpstr := ' text-anchor="start" ';
      writeouts(bstr+'</tspan></text>'); bstr := '';   // finish the <text> tag of the left part of the label
      if (odd(attr[p])) then
        begin   // small font
          outX := charX  - (fontsize2*cwf);
          outY := charY + (fontsize1*0.38);  // sic! (fontsize1)
          writeout('<text font-size="%d" %s x="%1.1f" y="%1.1f">',[fontsize2,tmpstr,outx,outy]);
        end else
        begin   // normal font
          outX := charX  - (fontsize1*cwf);
          outY := charY + (fontsize1*0.38);
          writeout('<text %s x="%1.1f" y="%1.1f">',[tmpstr,outx,outy]);
        end;
    end;
  // now print anything starting from the anchor to the right
  prev_b := 255;
  ylevel := 0;
  bstr := '';
  for i := anchorpos to length(purestr) do
    begin
      prev_ylevel := ylevel;
      outchar := purestr[i];
      trstr := outchar;  // if necessary, do some translations here
      b := attr[i];
      if (b <> prev_b) and (prev_b <> 255) then 
        bstr := bstr + '</tspan>';
      if odd(b) then
        begin
          if (b = 1) then   // subscript
            begin
              ylevel := dysub;
              delta_y := ylevel - prev_ylevel;
              if (b = prev_b) then 
                bstr := bstr + trstr
              else
                begin
                  if (abs(delta_y) > 0.1) then
                    bstr := bstr + format('<tspan font-size="%d" dy="%1.1f">%s',[fontsize2,delta_y,trstr],fsettings)
                  else
                    bstr := bstr + format('<tspan font-size="%d">%s',[fontsize2,trstr],fsettings);
                end;
            end;
          if (b = 3) then   // superscript
            begin
              ylevel := -1.0*dysub;    // may need adjustment!
              delta_y := ylevel - prev_ylevel;
              if (b = prev_b) then 
                bstr := bstr + trstr
              else
                begin
                  if (abs(delta_y) > 0.1) then
                    bstr := bstr + format('<tspan font-size="%d" dy="%1.1f">%s',[fontsize2,delta_y,trstr],fsettings)
                  else
                    bstr := bstr + format('<tspan font-size="%d">%s',[fontsize2,trstr],fsettings);
                end;
            end;
        end else
        begin
          ylevel := 0;
          delta_y := ylevel - prev_ylevel;
          if (b = prev_b) then 
            bstr := bstr + trstr
          else
            begin
              if (abs(delta_y) > 0.1) then
                bstr := bstr + format('<tspan dy="%1.1f">%s',[delta_y,outchar],fsettings)
              else
                bstr := bstr + format('<tspan font-size="%d">%s',[fontsize1,outchar],fsettings);
            end;
        end;
      prev_b := b;
    end;  // for
  writeouts(bstr+'</tspan></text>'); bstr := '';
end;

procedure printPSlabel_small(x:single;y:single;outstr:string;just:char);
var
  j : integer;
  charX,charY : integer;
  outXint,outYint : integer;
  instr : string;
  strlen : integer;
  outchar : char;
begin
  charX := round((x+xoffset)*blfactor);
  charY := round((y+yoffset)*blfactor);
  outXint := charX;
  outYint := charY - round(fontsize2*1.5);  // was: 20  / 0.8
  (*
  writeln((outXint),' dot ',(outYint),' dot moveto');
  writeln('CFontSub 0.6 0.6 0.6 setrgbcolor');
  *)
  updatebb(outXint, outYint);
  writeout('%d dot %d dot moveto',[outXint,outYint]);
  writeouts('CFontSub 0.6 0.6 0.6 setrgbcolor');
  if (just = 'L') then
    begin
      outchar := outstr[1];
      (*
      writeln('(',outchar,') stringwidth pop');
      writeln('2 div neg 0 rmoveto');
      *)
      writeouts('('+outchar+') stringwidth pop');
      writeouts('2 div neg 0 rmoveto');
    end;
  if (just = 'R') then
    begin
      outchar := outstr[length(outstr)];
      (*
      writeln('(',outchar,') stringwidth pop');
      writeln('2 div 0 rmoveto');
      writeln('CFontSub (',outstr,') stringwidth pop neg 0 rmoveto');
      *)
      writeouts('('+outchar+') stringwidth pop');
      writeouts('2 div 0 rmoveto');
      writeouts('CFontSub ('+outstr+') stringwidth pop neg 0 rmoveto');
    end;
  if (just = 'C') then
    begin
      outchar := outstr[length(outstr)];
      (*
      writeln('(',outchar,') stringwidth pop');
      writeln('2 div 0 rmoveto');
      writeln('CFontSub (',outstr,') stringwidth pop 2 div neg 0 rmoveto');
      *)
      writeouts('('+outchar+') stringwidth pop');
      writeouts('2 div 0 rmoveto');
      writeouts('CFontSub ('+outstr+') stringwidth pop 2 div neg 0 rmoveto');
    end;
  instr := outstr;
  strlen := length(instr);
  for j := 1 to strlen do
    begin
      outchar := instr[(j)];
      outstr := outchar;
      writeouts('('+outstr+') show');
    end;
  writeouts('0 0 0 setrgbcolor');
end;

procedure printSVGlabel_small(x:single;y:single;outstr:string;just:char);
var
  charX,charY : single;
  outX,outY : single;
  dysub : single;
  strwidth : single;  // v0.2c
begin
  dysub := (fontsize2*0.7);
  charX := (x+xoffset)*blfactor*svg_factor;
  charY := round((y+yoffset)*blfactor*-svg_factor + svg_yoffset);
  if (just = 'L') then
    begin
      outX := charX  - (fontsize1*0.35);
      outY := charY + (fontsize1*0.38);
      strwidth := length(outstr)*fontsize2*1.1;  
      chk_svg_max_xy((outX+strwidth),outY);
      writeout('<text fill="#999999" font-size="%d" x="%1.1f" y="%1.1f">',[fontsize2,outx,outy]);
    end;
  if (just = 'R') then
    begin
      outX := charX  + (fontsize1*0.35);
      outY := charY + (fontsize1*0.38);
      writeout('<text fill="#999999" font-size="%d" text-anchor="end" x="%1.1f" y="%1.1f">',[fontsize2,outx,outy]);
    end;
  if (just = 'C') then
    begin
      outX := charX;
      outY := charY + (fontsize1*0.38);
      strwidth := length(outstr)*0.5*fontsize2*1.1;  
      chk_svg_max_xy((outX+strwidth),outY);
      writeout('<text fill="#999999" font-size="%d" text-anchor="middle" x="%1.1f" y="%1.1f">',[fontsize2,outx,outy]);
    end;
  writeout('<tspan>%s</tspan>',[outstr]);
  writeouts('</text>');
end;

procedure printPSsgroups;
var
  i,a : integer;
  px,py : single;
begin
  if (n_sgroups > 0) then
    begin
      for i := 1 to n_sgroups do
        begin
          with sgroup^[i] do
            begin
              if (sgtype = 'SUP') and (length(sglabel) > 0) then
                begin
                  a := anchor;
                  px := atom^[a].x;
                  py := atom^[a].y;
                  printPSlabel_autosub(px,py,sglabel,justification);
                end;
              if (sgtype = 'DAT') and (length(sglabel) > 0) then
                begin
                  px := x;
                  py := y;
                  printPSlabel_small(px,py,sglabel,'C');
                end;
            end;
        end;
    end;
end;

procedure printSVGsgroups;
var
  i,a : integer;
  px,py : single;
begin
  if (n_sgroups > 0) then
    begin
      for i := 1 to n_sgroups do
        begin
          with sgroup^[i] do
            begin
              if (sgtype = 'SUP') and (length(sglabel) > 0) then
                begin
                  a := anchor;
                  px := atom^[a].x;
                  py := atom^[a].y;
                  printSVGlabel_autosub(px,py,sglabel,justification);
                end;
              if (sgtype = 'DAT') and (length(sglabel) > 0) then
                begin
                  px := x;
                  py := y;
                  //printSVGlabel_autosub(px,py,sglabel,justification);
                  printSVGlabel_small(px,py,sglabel,'C');
                end;
            end;
        end;
    end;
end;

procedure printPSaliases;
var
  i,a : integer;
  px,py : single;
  alias : string;
  just  : char;
begin
  if (n_atoms > 0) then
    begin
      for i := 1 to n_atoms do
        begin
          alias := atom^[i].alias;
          just := 'L';
          if (atom^[i].a_just = 1) then just := 'R';
          if (atom^[i].a_just = 2) then just := 'C';
          if (alias <> '') then
            begin
              px := atom^[i].x;
              py := atom^[i].y;
              printPSlabel_autosub(px,py,alias,just);
            end;
        end;
    end;
end;

procedure printSVGaliases;
var
  i,a : integer;
  px,py : single;
  alias : string;
  just  : char;
begin
  if (n_atoms > 0) then
    begin
      for i := 1 to n_atoms do
        begin
          alias := atom^[i].alias;
          just := 'L';
          if (atom^[i].a_just = 1) then just := 'R';
          if (atom^[i].a_just = 2) then just := 'C';
          if (alias <> '') then
            begin
              px := atom^[i].x;
              py := atom^[i].y;
              printSVGlabel_autosub(px,py,alias,just);
            end;
        end;
    end;
end;

procedure write_PS_atomlabels;
begin
  printPSchars;
  printPSaliases;  // v0.2b
  if opt_sgroups then printPSsgroups;
end;

procedure write_SVG_atomlabels;
begin
  printSVGchars; // example: <text fill="#FF0000" font-size="',fontsize2,'" font-weight="bold" text-anchor="middle" x="214.20763486057" y="214.97172994938">O</text>
  printSVGaliases;  // v0.2b
  if opt_sgroups then printSVGsgroups;
end;

procedure findzorder;
var
  i, j, m, n : integer;
  az, minaz : double;
begin
  for i := 1 to n_atoms do 
    begin
      zorder[i] := 0;
      atom^[i].tag := false;
    end;
  n := 0;
  for j := 1 to n_atoms do
    begin
      minaz := 10000;
      for i := 1 to n_atoms do
        begin
          az := atom^[i].z;
          if (az <= minaz) and (atom^[i].tag = false) then
            begin
              minaz := az;
              m := i;
            end;    
        end;  // now we have the minimal Z
      inc(n);
      zorder[n] := m;
      atom^[m].tag := true;  
    end;
end;

procedure printchargedcarbons;
const
  cpright = 1;
  cpleft  = 2;
  cpup    = 3;
  cpdown  = 4;
var
  i, j : integer;
  chg : integer;
  rad : integer;
  chgstr, outstr : string;
  outchar : char;
  cpos : integer;
  charx, chary : integer;
  outXint, outYint : integer;
  sg : boolean;  // v0.2a
begin
  if opt_color then writeln('1 0 0 setrgbcolor');
  for i := 1 to n_atoms do
    begin
      chg := 0;
      chgstr := '';
      sg := false;
      if opt_sgroups then sg := atom^[i].sg;  // v0.2a
      if (sg = false) and (atom^[i].element = 'C ') and
         (atom^[i].hidden = true) and
         ((atom^[i].formal_charge <> 0) or (atom^[i].radical_type > 0)) then
        begin
          chg := atom^[i].formal_charge;
          rad := atom^[i].radical_type;
          if (abs(chg) > 1) then chgstr := inttostr(abs(chg));
          if (chg < 0) then chgstr := chgstr + '_';
          if (chg > 0) then chgstr := chgstr + '+';
          if (rad = 1) then chgstr := chgstr + ':';
          if (rad = 2) then chgstr := chgstr + '.';
          if (rad = 3) then chgstr := chgstr + '=';
          cpos := findHpos(i);
          writeouts('CFontChg');
          charX := round((atom^[i].x+xoffset)*blfactor);
          charY := round((atom^[i].y+yoffset)*blfactor);
          outXint := charX;
          outYint := charY - round(fontsize1*0.8);  // was: 20
          updatebb(outXint, outYint);
          outstr := chgstr;
          writeout('%d dot %d dot moveto',[outXint,outYint]);
          writeouts('CFontChg ('+outstr+') stringwidth pop');
          writeouts('2 div neg 0 rmoveto');
          case cpos of
            cpright : begin
                        writeouts('('+outstr+') stringwidth pop 1.1 div 0 rmoveto');
                      end;
            cpleft  : begin
                        writeouts('('+outstr+') stringwidth pop 1.1 div neg 0 rmoveto');
                      end;
            cpup    : begin
                        writeout(' 0 %d rmoveto',[round(fontsize1*0.4)]);
                      end;
            cpdown  : begin
                        writeout(' 0 %d neg rmoveto',[round(fontsize1*0.4)]);
                      end;
          end;  // case 
          for j := 1 to length(outstr) do
            begin
              outchar := outstr[j];
              if (pos(outchar,'+_:.=') > 0) then writeouts('CFontChg') else writeouts('CFontSub');
              if outchar = '_' then writeouts('Minus') else
                if outchar = ':' then writeouts('Rad1') else
                  if outchar = '.' then writeouts('Rad2') else
                    if outchar = '=' then writeouts('Rad3') else writeouts('('+outchar+') show');
            end;
        end; 
    end;
  if opt_color then writeouts('0 0 0 setrgbcolor');
end;

procedure printchargedcarbons_SVG;
const
  cpright = 1;
  cpleft  = 2;
  cpup    = 3;
  cpdown  = 4;
var
  i, j : integer;
  chg : integer;
  rad : integer;
  chgstr, outstr : string;
  outchar : char;
  cpos : integer;
  charx, chary : single;
  outX, outY : single;
  bstr : string;
  chgfs : single;
begin
  chgfs := (fontsize1+fontsize2)/2;
  for i := 1 to n_atoms do
    begin
      chg := 0;
      chgstr := '';
      if (atom^[i].element = 'C ') and
         (atom^[i].hidden = true) and
         ((atom^[i].formal_charge <> 0) or (atom^[i].radical_type > 0)) then
        begin
          chg := atom^[i].formal_charge;
          rad := atom^[i].radical_type;
          if (abs(chg) > 1) then chgstr := inttostr(abs(chg));
          if (chg < 0) then chgstr := chgstr + '_';
          if (chg > 0) then chgstr := chgstr + '+';
          if (rad = 1) then chgstr := chgstr + ':';
          if (rad = 2) then chgstr := chgstr + '.';
          if (rad = 3) then chgstr := chgstr + '=';
          cpos := findHpos(i);
          charX := (atom^[i].x+xoffset)*blfactor*svg_factor;
          charY := (atom^[i].y+yoffset)*blfactor*-svg_factor + svg_yoffset;
          outX := charX;
          outY := charY + fontsize1*0.2;  // was: 20
          outstr := chgstr;
          case cpos of
            cpright : begin
                        bstr := '';
                        writeout('<text style="font-size: %1.1fpx" x="%1.1f" y="%1.1f">',[chgfs,(outx+3),outy]);
                        for j := 1 to length(outstr) do
                          begin
                            outchar := outstr[j];
                            if (outchar = '_') then 
                              bstr := bstr + format('<tspan dy="-5">%s</tspan>',[outchar],fsettings) 
                            else
                              if (outchar = '.') then 
                                bstr := bstr + '<tspan dy="0">&#8226;</tspan>' else
                                  if (outchar = '=') then 
                                    bstr := bstr + '<tspan dy="0">^^</tspan>' else
                                    if (outchar = ':') then 
                                      bstr := bstr + '<tspan font-weight="bold" dy="0">:</tspan>' else
                                        bstr := bstr + format('<tspan dy="0">%s</tspan>',[outchar],fsettings);
                          end;
                        writeouts(bstr);
                        bstr := '';
                      end;
            cpleft  : begin
                        bstr := '';
                        writeout('<text style="font-size: %1.1fpx" text-anchor="end" x="%1.1f" y="%1.1f">',[chgfs,(outx-3),outy]);
                        for j := 1 to length(outstr) do
                          begin
                            outchar := outstr[j];
                            if (outchar = '_') then 
                              bstr := bstr + format('<tspan dy="-5">%s</tspan>',[outchar],fsettings) 
                            else
                              if (outchar = '.') then 
                                bstr := bstr + '<tspan dy="0">&#8226;</tspan>' else
                                  if (outchar = '=') then 
                                    bstr := bstr + '<tspan dy="0">^^</tspan>' else
                                    if (outchar = ':') then 
                                      bstr := bstr + '<tspan font-weight="bold" dy="0">:</tspan>' else
                                        bstr := bstr + format('<tspan dy="0">%s</tspan>',[outchar],fsettings);
                          end;
                        writeouts(bstr);
                        bstr := '';
                      end;
            cpup    : begin
                        bstr := '';
                        writeout('<text style="font-size: %1.1fpx" text-anchor="middle" x="%1.1f" y="%1.1f">',[chgfs,outx,(outy-5)]);
                        for j := 1 to length(outstr) do
                          begin
                            outchar := outstr[j];
                            if (outchar = '_') then 
                              bstr := bstr + format('<tspan dy="-5">%s</tspan>',[outchar],fsettings) 
                            else
                              if (outchar = '.') then 
                                bstr := bstr + '<tspan dy="0">&#8226;</tspan>' else
                                  if (outchar = '=') then 
                                    bstr := bstr + '<tspan dy="0">^^</tspan>' else
                                    if (outchar = ':') then 
                                      bstr := bstr + '<tspan font-weight="bold" dy="0">:</tspan>' else
                                        bstr := bstr + format('<tspan dy="0">%s</tspan>',[outchar],fsettings);
                          end;
                        writeouts(bstr);
                        bstr := '';
                      end;
            cpdown  : begin
                        bstr := '';
                        writeout('<text style="font-size: %1.1fpx" text-anchor="middle" x="%1.1f" y="%1.1f">',[chgfs,outx,(outy+5)]);
                        for j := 1 to length(outstr) do
                          begin
                            outchar := outstr[j];
                            if (outchar = '_') then 
                              bstr := bstr + format('<tspan dy="-5">%s</tspan>',[outchar],fsettings) 
                            else
                              if (outchar = '.') then 
                                bstr := bstr + '<tspan dy="0">&#8226;</tspan>' else
                                  if (outchar = '=') then 
                                    bstr := bstr + '<tspan dy="0">^^</tspan>' else
                                    if (outchar = ':') then 
                                      bstr := bstr + '<tspan font-weight="bold" dy="0">:</tspan>' else
                                        bstr := bstr + format('<tspan dy="0">%s</tspan>',[outchar],fsettings);
                          end;
                        writeouts(bstr);
                        bstr := '';
                      end;
          end;  // case 
          writeouts('</text>');
        end; 
    end;  // for
end;

procedure write_PS_bonds_and_boxes;
var
  i, j, a, b : integer;
  nb : neighbor_rec;
  nnb : integer;
  el : str2;
  anchor, chgstr : string;
  bbx, bby : integer;
  tmpstr : string;
  sga, sgb : boolean;  // v0.2a
begin
  chk_hidden;
  if (n_bonds > 0) then
    begin
      for i := 1 to n_bonds do bond^[i].drawn := false;
    end;  
  for i := 1 to n_atoms do
    begin
      a := zorder[i];
      if (opt_stripH = true) then    // v0.1f
        begin
          nb := get_neighbors(a);
          nnb := atom^[a].neighbor_count;
        end else
        begin
          nb := get_allneighbors(a);
          nnb := atom^[a].neighbor_count + atom^[a].Hexp;
        end;
      el := atom^[a].element;
      sga := false;
      if opt_sgroups then sga := atom^[a].sg;
      anchor := el;
      if anchor[2] = ' ' then delete(anchor,2,1) else    // v0.1f
        begin
          tmpstr := anchor;
          tmpstr := lowercase(tmpstr);
          tmpstr[1] := upcase(tmpstr[1]);
          anchor := copy(tmpstr,1,2);
        end;
      if (atom^[a].formal_charge) <> 0 then chgstr := '+' else chgstr := '';
      if (nnb > 0) then
        begin
          for j := 1 to nnb do
            begin
              b := get_bond(a,nb[j]);
              sgb := false;
              if opt_sgroups then sgb := bond^[b].sg;
              if (bond^[b].drawn = false) and (sgb = false) then
                begin
                  print_PS_bond(b);
                  bond^[b].drawn := true;
                end;  
            end;
        end;  
      if (atom^[a].hidden = false) and ((atom^[a].sg = false) or (opt_sgroups = false)) or (atom^[a].alias <> '') then // v0.2b
        begin
          bbX := round((atom^[a].x+xoffset)*blfactor);
          bbY := round((atom^[a].y+yoffset)*blfactor);
          if (atom^[a].alias <> '') then
            begin
              anchor := 'M'; chgstr := '';
            end;
          printBB(bbX, bbY, anchor, chgstr);        
        end;
      if opt_sgroups and (n_sgroups > 0) then
        begin
          for j := 1 to n_sgroups do
            begin
              if (sgroup^[j].anchor = a) then
                begin
                  bbX := round((atom^[a].x+xoffset)*blfactor);
                  bbY := round((atom^[a].y+yoffset)*blfactor);
                  printBB(bbX, bbY, anchor, chgstr);        
                end;
            end;    
        end;
    end;
  printchargedcarbons;
end;

procedure write_SVG_bonds_and_boxes;
var
  i, j, a, b : integer;
  nb : neighbor_rec;
  nnb : integer;
  el : str2;
  anchor, chgstr : string;
  bbx, bby : single;
  tmpstr : string;
  sga, sgb : boolean;  
begin
  chk_hidden;
  if (n_bonds > 0) then
    begin
      for i := 1 to n_bonds do bond^[i].drawn := false;
    end;
  for i := 1 to n_atoms do
    begin
      a := zorder[i];
      if (opt_stripH = true) then    // v0.1f
        begin
          nb := get_neighbors(a);
          nnb := atom^[a].neighbor_count;
        end else
        begin
          nb := get_allneighbors(a);
          nnb := atom^[a].neighbor_count + atom^[a].Hexp;
        end;
      el := atom^[a].element;
      sga := false;
      if opt_sgroups then sga := atom^[a].sg;
      anchor := el;
      if anchor[2] = ' ' then delete(anchor,2,1) else    // v0.1f
        begin
          tmpstr := anchor;
          tmpstr := lowercase(tmpstr);
          tmpstr[1] := upcase(tmpstr[1]);
          anchor := copy(tmpstr,1,2);
        end;
      if (atom^[a].formal_charge) <> 0 then chgstr := '+' else chgstr := '';
      if (nnb > 0) then
        begin
          for j := 1 to nnb do
            begin
              b := get_bond(a,nb[j]);
              sgb := false;
              if opt_sgroups then sgb := bond^[b].sg;
              if (bond^[b].drawn = false) and (sgb = false) then
                begin
                  print_SVG_bond(b);
                  bond^[b].drawn := true;
                end;  
            end;
        end;  
      if (atom^[a].hidden = false) and ((atom^[a].sg = false) or (opt_sgroups = false)) then
        begin
          bbX := ((atom^[a].x+xoffset)*blfactor*svg_factor);
          bbY := ((atom^[a].y+yoffset)*blfactor*-svg_factor + svg_yoffset);
          printSVGBB(bbX, bbY, anchor, chgstr);        
        end;
      if opt_sgroups and (n_sgroups > 0) then
        begin
          for j := 1 to n_sgroups do
            begin
              if (sgroup^[j].anchor = a) then
                begin
                  bbX := ((atom^[a].x+xoffset)*blfactor*svg_factor);
                  bbY := ((atom^[a].y+yoffset)*blfactor*-svg_factor + svg_yoffset);
                  printSVGBB(bbX, bbY, anchor, chgstr);        
                end;
            end;    
        end;
    end;
  printchargedcarbons_SVG;
end;

procedure write_SVG_bonds_and_boxes_compact;
var
  i, j, a, b : integer;
  nb : neighbor_rec;
  nnb : integer;
  el : str2;
  anchor, chgstr : string;
  bbx, bby : single;
  tmpstr : string;
  sga, sgb : boolean; 
  n_drawn : integer; 
begin
  {$IFDEF debug}
  debugoutput('entering compact mode');
  {$ENDIF}
  chk_hidden;
  if (n_bonds > 0) then
    begin
      for i := 1 to n_bonds do 
        begin
          bond^[i].drawn := false;
          // draw all bonds with non-path-compatible bond type (wedge etc.)
          print_SVG_bond_special(i);
        end;
    end;  
  n_drawn := 0;
  // write <path> opening tag
  writeout('<path stroke="#000000" stroke-width="%1.1f" d="',[linewidth]);
  for i := 1 to n_atoms do   // first round: bonds only
    begin
      a := zorder[i];
      if (opt_stripH = true) then    // v0.1f
        begin
          nb := get_neighbors(a);
          nnb := atom^[a].neighbor_count;
        end else
        begin
          nb := get_allneighbors(a);
          nnb := atom^[a].neighbor_count + atom^[a].Hexp;
        end;
      el := atom^[a].element;
      sga := false;
      if opt_sgroups then sga := atom^[a].sg;
      anchor := el;
      if anchor[2] = ' ' then delete(anchor,2,1) else    // v0.1f
        begin
          tmpstr := anchor;
          tmpstr := lowercase(tmpstr);
          tmpstr[1] := upcase(tmpstr[1]);
          anchor := copy(tmpstr,1,2);
        end;
      if (atom^[a].formal_charge) <> 0 then chgstr := '+' else chgstr := '';
      if (nnb > 0) then
        begin
          for j := 1 to nnb do
            begin
              b := get_bond(a,nb[j]);
              sgb := false;
              if opt_sgroups then sgb := bond^[b].sg;
              if (bond^[b].drawn = false) and (sgb = false) then
                begin
                  print_SVG_bond(b);
                  bond^[b].drawn := true;
                  inc(n_drawn);
                  if (n_drawn > 3) then
                    begin
                      n_drawn := 0;
                    end;
                end;  
            end;
        end;  
      if (atom^[a].hidden = false) and ((atom^[a].sg = false) or (opt_sgroups = false)) then
        begin
          bbX := ((atom^[a].x+xoffset)*blfactor*svg_factor);
          bbY := ((atom^[a].y+yoffset)*blfactor*-svg_factor + svg_yoffset);
          //printSVGBB(bbX, bbY, anchor, chgstr);        
        end;
      if opt_sgroups and (n_sgroups > 0) then
        begin
          for j := 1 to n_sgroups do
            begin
              if (sgroup^[j].anchor = a) then
                begin
                  bbX := ((atom^[a].x+xoffset)*blfactor*svg_factor);
                  bbY := ((atom^[a].y+yoffset)*blfactor*-svg_factor + svg_yoffset);
                  //printSVGBB(bbX, bbY, anchor, chgstr);        
                end;
            end;    
        end;
    end;  // end first round
  // write <path> closing tag
  writeouts('" />');
  for i := 1 to n_atoms do   // sexcond round: atom boxes
    begin
      a := zorder[i];
      if (opt_stripH = true) then    // v0.1f
        begin
          nb := get_neighbors(a);
          nnb := atom^[a].neighbor_count;
        end else
        begin
          nb := get_allneighbors(a);
          nnb := atom^[a].neighbor_count + atom^[a].Hexp;
        end;
      el := atom^[a].element;
      sga := false;
      if opt_sgroups then sga := atom^[a].sg;
      anchor := el;
      if anchor[2] = ' ' then delete(anchor,2,1) else    // v0.1f
        begin
          tmpstr := anchor;
          tmpstr := lowercase(tmpstr);
          tmpstr[1] := upcase(tmpstr[1]);
          anchor := copy(tmpstr,1,2);
        end;
      if (atom^[a].formal_charge) <> 0 then chgstr := '+' else chgstr := '';
      if (nnb > 0) then
        begin
          for j := 1 to nnb do
            begin
              b := get_bond(a,nb[j]);
              sgb := false;
              if opt_sgroups then sgb := bond^[b].sg;
              if (bond^[b].drawn = false) and (sgb = false) then
                begin
                  //print_SVG_bond(b);
                  bond^[b].drawn := true;
                end;  
            end;
        end;  
      if (atom^[a].hidden = false) and ((atom^[a].sg = false) or (opt_sgroups = false)) then
        begin
          bbX := round((atom^[a].x+xoffset)*blfactor*svg_factor);
          bbY := round((atom^[a].y+yoffset)*blfactor*-svg_factor + svg_yoffset);
          printSVGBB(bbX, bbY, anchor, chgstr);        
        end;
      if opt_sgroups and (n_sgroups > 0) then
        begin
          for j := 1 to n_sgroups do
            begin
              if (sgroup^[j].anchor = a) then
                begin
                  bbX := round((atom^[a].x+xoffset)*blfactor*svg_factor);
                  bbY := round((atom^[a].y+yoffset)*blfactor*-svg_factor + svg_yoffset);
                  printSVGBB(bbX, bbY, anchor, chgstr);        
                end;
            end;    
        end;
    end;  // end second round
  printchargedcarbons_SVG;
end;

procedure write_PS_brackets;  // v0.1f
var
  i : integer;
  x1, y1, x2, y2, x3, y3, x4, y4 : single;
  xmax, ymin : single;
  brtype : integer;
  brlabel : string;
begin
  if (bracket = nil) or (n_brackets < 1) then exit;
  for i := 1 to n_brackets do
    begin
      brtype  := bracket^[i].brtype;
      brlabel := bracket^[i].brlabel;
      x1 := bracket^[i].x1; y1 := bracket^[i].y1;
      x2 := bracket^[i].x2; y2 := bracket^[i].y2;
      x3 := bracket^[i].x3; y3 := bracket^[i].y3;
      x4 := bracket^[i].x4; y4 := bracket^[i].y4;
      xmax := -9999; 
      if x1 > xmax then xmax := x1; if x2 > xmax then xmax := x2;
      if x3 > xmax then xmax := x3; if x4 > xmax then xmax := x4;
      ymin := 9999; 
      if y1 < ymin then ymin := y1; if y2 < ymin then ymin := y2;
      if y3 < ymin then ymin := y3; if y4 < ymin then ymin := y4;
      print_PS_squarebracket(x1,y1,x2,y2,x3,y3,x4,y4,brlabel);
    end;
  writeouts('stroke');
end;

procedure write_SVG_brackets;  // v0.1f
var
  i : integer;
  x1, y1, x2, y2, x3, y3, x4, y4 : single;
  xmax, ymin : single;
  brtype : integer;
  brlabel : string;
begin
  if (bracket = nil) or (n_brackets < 1) then exit;
  for i := 1 to n_brackets do
    begin
      brtype  := bracket^[i].brtype;
      brlabel := bracket^[i].brlabel;
      x1 := bracket^[i].x1; y1 := bracket^[i].y1;
      x2 := bracket^[i].x2; y2 := bracket^[i].y2;
      x3 := bracket^[i].x3; y3 := bracket^[i].y3;
      x4 := bracket^[i].x4; y4 := bracket^[i].y4;
      xmax := -9999; 
      if x1 > xmax then xmax := x1; if x2 > xmax then xmax := x2;
      if x3 > xmax then xmax := x3; if x4 > xmax then xmax := x4;
      ymin := 9999; 
      if y1 < ymin then ymin := y1; if y2 < ymin then ymin := y2;
      if y3 < ymin then ymin := y3; if y4 < ymin then ymin := y4;
      print_SVG_squarebracket(x1,y1,x2,y2,x3,y3,x4,y4,brlabel);
    end;
end;

procedure write_XY_comment;
var
  i : integer;
  molX, molY : integer;
  //molZ : integer;
  auxstr : string;
  el : str2;
  tmpstr : string;
  visible : boolean;
begin
  if n_atoms < 1 then exit;
  writeln('% appendix: actual & original XY coordinates ("dots", Angstroms)');
  for i := 1 to n_atoms do
    begin
      el := atom^[i].element;
      visible := false;
      if (not ((el = 'H ') and opt_stripH)) or (atom^[i].hidden = false) then visible := true;
      molX := round((atom^[i].x+xoffset)*blfactor);
      molY := round((atom^[i].y+yoffset)*blfactor);
      //molZ := round((atom^[i].z)*blfactor);
      write('%   atom ');
      auxstr := '';
      str(i,auxstr);
      while (length(auxstr)<3) do auxstr := ' ' + auxstr;
      write(auxstr,': ');
      str(molX,auxstr);
      while (length(auxstr)<4) do auxstr := ' ' + auxstr;
      write(auxstr,' ');
      str(molY,auxstr);
      while (length(auxstr)<4) do auxstr := ' ' + auxstr;
      write(auxstr,'   ');
      if (length(el)=1) then el := el + ' ';
      tmpstr := el;
      tmpstr := lowercase(el);
      tmpstr[1] := upcase(tmpstr[1]);
      el := tmpstr;
      write(el);
      if visible then write('  ') else write('/ ');
      str(atom^[i].x_orig:1:4,auxstr);
      while (length(auxstr)<8) do auxstr := ' ' + auxstr;
      write(auxstr,' ');
      str(atom^[i].y_orig:1:4,auxstr);
      while (length(auxstr)<8) do auxstr := ' ' + auxstr;
      write(auxstr,' ');
      writeln;
    end;
end;


procedure write_PS;
var
  i : integer;
  ha_el : str2;
begin
  if (n_heavyatoms = 1) then
    begin
      ha_el := '  ';
      for i := 1 to n_atoms do
        begin
          if is_heavyatom(i) then ha_el := atom^[i].element;
        end;
      if (ha_el = 'C ') then opt_stripH := false;  // methane
    end; 
  if (n_heavyatoms = 0) then opt_stripH := false;
  findzorder;
  write_PS_bonds_and_boxes;
  write_PS_atomlabels;
  if opt_color then writeouts('0 0 0 setrgbcolor');
  if n_brackets > 0 then write_PS_brackets;
  if opt_showmolname and (molname <> '') then
    begin
      //writeout('100 dot %d dot moveto (%s) show',[round(maxY*blfactor),molname]);
      writeout('CFont 0 fs1 %d dot %d dot moveto (%s) show', [bboxleft-round(bboxmargin*fontsize1),bboxtop+blfactor,molname]);
      updatebb(bboxleft, bboxtop+blfactor);
    end;
  if not rxn_mode then
    begin
      //here comes the entire PS output when not in reaction mode
      write_PS_init;  // new position, including the BB definition
      writeln(outbuffer.text);
      outbuffer.clear;
      if not opt_eps then writeln('showpage');
      writeln;
      {$IFDEF csearch_extensions}
      if progmode = pmMol2PS then write_XY_comment;
      {$ENDIF}
      {$IFDEF debug}
      debugoutput('number of brackets: '+inttostr(n_brackets)+' number of Sgroups: '+inttostr(n_sgroups));
      {$ENDIF}
      writeln('% ----------------------end of image------------------------');
    end;
end;

function get_ymin:single;
var
  i : integer;
  r, ytmp : single;
begin
  r := 10000;
  if (n_atoms > 0) then
    begin
      for i := 1 to n_atoms do
        begin
          ytmp := (atom^[i].y+yoffset)*blfactor*-svg_factor + svg_yoffset;
          if ytmp < r then r := ytmp;
        end;
    end;
  get_ymin := r;
end;

procedure write_SVG_dimensions;
begin
  writeln('<!-- found XY values for adjusting width, height and viewbox: -->');
  writeln('<!-- max_X:  ',round(svg_max_x)+20,'  -->');  // add a little safety margin of 20
  writeln('<!-- max_Y:  ',round(svg_max_y)+25,'  -->');  // add a little safety margin of 20
  writeln('<!-- min_Y:  ',round(svg_min_y)-25,'  -->');  // add a little safety margin of 20
  writeln('<!-- yshift: ',max_ytrans,'  -->');
end;


procedure write_SVG;
var
  i : integer;
  ha_el : str2;
  ymin, outY : single;
  //ytrans : integer;    // moved to the global variables, v0.2c
  //ymargin : integer;
  is_flat : boolean;
begin
  ytrans := 0;  // v0.2c
  if opt_showmolname then ymargin := 100 else ymargin := 30;
  if (n_heavyatoms = 1) then
    begin
      ha_el := '  ';
      for i := 1 to n_atoms do
        begin
          if is_heavyatom(i) then ha_el := atom^[i].element;
        end;
      if (ha_el = 'C ') then opt_stripH := false;  // methane
    end; 
  if (n_heavyatoms = 0) then opt_stripH := false;
  findzorder;
  ymin := get_ymin;
  if (ymin < 0) then
    begin
      ytrans := round(abs(ymin)+ymargin);
      writeout('<g transform="translate(0,%d)">',[ytrans]);
    end else
    begin
      if (rxn_mode = false) and (ymin > ymargin) then
        begin
          ytrans := -round(ymin-ymargin);
          writeout('<g transform="translate(0,%d)">',[ytrans]);
        end else writeouts('<g>');
    end;
  if (abs(ytrans) > abs(max_ytrans)) then max_ytrans := ytrans;  // v0.2c
  // check if we can use "compact" mode for really flat molecules;  v0.2c
  if (n_atoms > 0) then
    begin
      is_flat := true;
      for i := 1 to n_atoms do
        begin
          if (atom^[i].z <> 0) then is_flat := false;
        end;
      if (n_bonds > 0) then
        begin
          for i := 1 to n_bonds do
            begin
              // we have to worry only about "down" bonds, as their atom labels could be crossed
              // by a bond at a higher X level
              if (bond^[i].stereo = bstereo_down) then is_flat := false;
            end;
        end;
      if is_flat then svg_mode := 2;
    end;
  {$IFDEF debug}
  debugoutput('svg mode = '+inttostr(svg_mode));
  {$ENDIF}
  if (svg_mode = 1) then write_SVG_bonds_and_boxes;
  if (svg_mode = 2) then write_SVG_bonds_and_boxes_compact;
  svg_mode := 1;  // rest mode for brackets
  write_SVG_atomlabels;
  if n_brackets > 0 then write_SVG_brackets;
  writeouts('</g>');
  if not rxn_mode then
    begin
      writeouts('</g>');
      writeouts('</svg>');
      // here comes the SVG header with the corrected dimensions
      write_SVG_init;
      // here comes the content of the output buffer
      writeln(outbuffer.text);
      outbuffer.clear;                    
      // here comes the list of original  dimensions (for post-processing)
      write_SVG_dimensions;
    end;
end;

procedure loadrgbtable(rgbfilename:string);
var
  i, pp : integer;
  rline : string;
  elstr : string;
  rval, gval, bval : integer;
begin
  assign(rgbfile,rgbfilename);
  reset(rgbfile);
  i := 0;
  while not eof(rgbfile) do
    begin
      readln(rgbfile,rline);
      trimleft(rline); trimright(rline);
      pp := pos('#',rline);
      if (pp > 0) then rline := copy(rline,1,(pp-1)); 
      if (rline <> '') and (i < max_rgbentries) then
        begin
          inc(i);
          elstr := '';
          while (length(rline)>0) and (rline[1] <> ' ') and (rline[1] <> TAB) do
            begin
              elstr := elstr + rline[1];
              delete(rline,1,1);
            end;
          if (length(elstr) > 2) then elstr := copy(elstr,1,2);
          rgbtable[i].element := elstr;
          rval := left_int(rline);
          gval := left_int(rline);
          bval := left_int(rline);
          if (rval < 0) or (gval < 0) or (bval < 0) or
             (rval > 255) or (gval > 255) or (bval > 255) then
            begin
              rval := 0; gval := 0; bval := 0;
            end;
          rgbtable[i].r := rval;
          rgbtable[i].g := gval;
          rgbtable[i].b := bval;
        end;
    end;
  close(rgbfile);
end;


procedure chk_sgbonds;
var
  i : integer;
  a1, a2 : integer;
begin
  if (n_bonds > 0) then
    begin
      for i := 1 to n_bonds do
        begin
          a1 := bond^[i].a1;
          a2 := bond^[i].a2;
          if (atom^[a1].sg = true) and (atom^[a2].sg = true) then bond^[i].sg := true else bond^[i].sg := false;
        end;
    end;
end;


procedure process_mol;
begin
  chk_ringbonds;
  if ringsearch_mode = rs_ssr then remove_redundant_rings;
  if n_rings = max_rings then
    begin
      ringsearch_mode := rs_ssr;
      clear_rings;
      max_vringsize := 10;
      chk_ringbonds;
      remove_redundant_rings;
    end;
  update_ringcount;
  update_atypes;
  update_Htotal;
  chk_arom;
  if (ringsearch_mode = rs_ssr) then
    begin
      repeat
        prev_n_ar := count_aromatic_rings;
        chk_arom;
        n_ar := count_aromatic_rings;
      until ((prev_n_ar - n_ar) = 0);
    end;
  if not rxn_mode then adjust_mol;  // v0.2
  refine_bonds;
  if opt_sgroups then chk_sgbonds;  // v0.2a
  if (progmode = pmMol2PS)  then write_PS;
  if (progmode = pmMol2SVG) then write_SVG;
  zap_molecule;
  molbufindex := 0;
end;


procedure get_xminmax(var xmin,xmax:single);
var
  i : integer;
  tmin, tmax, xcurr : single;
  al : string;
  lstr, rstr : string;
  just : char;
  ap : integer;
  lw,rw : double;
begin
  tmin := 1000;
  tmax := -1000;
  if n_atoms > 0 then
    begin
      for i := 1 to n_atoms do
        begin
          if atom^[i].x < tmin then tmin := atom^[i].x;
          if atom^[i].x > tmax then tmax := atom^[i].x;
          // check also for left parts of alias labels (the right parts
          // will be checked elsewhere)   v0.4
          if atom^[i].alias <> '' then
            begin
              xcurr := atom^[i].x;
              al := atom^[i].alias;
              case atom^[i].a_just of
                0 : just := 'L';
                1 : just := 'R';
                2 : just := 'C';
              end;
              while (pos('\S',al)>0) do delete(al,pos('\S',al),2);
              while (pos('\s',al)>0) do delete(al,pos('\s',al),2);
              while (pos('\n',al)>0) do delete(al,pos('\n',al),2);
              ap := pos('^',al);
              if (ap = 0) then
                begin
                  if (just = 'L') then ap := 1;
                  if (just = 'R') then ap := length(al)-1;
                  if (just = 'C') then ap := length(al) div 2;
                end;
              if (ap > 1) then
                begin
                  lstr := copy(al,1,(ap-1));
                  lw := 0.375*get_stringwidth(fontsize1,lstr);
                  if (xcurr - lw) < tmin then tmin := (xcurr - lw);
                end;
              if (ap < (length(al)-1)) then
                begin
                  rstr := copy(al,(ap+1),(length(al)-ap));
                  rw := 0.375*get_stringwidth(fontsize1,rstr);
                  if (xcurr + rw) > tmax then tmax := (xcurr + rw);
                end;
            end;
        end;
    end;
  xmin := tmin;
  xmax := tmax;
end;


procedure shift_x(xshift:single);
var
  i : integer;
begin
  if n_atoms > 0 then
    begin
      for i := 1 to n_atoms do atom^[i].x := atom^[i].x + xshift;
    end;
end;

begin  // main routine
  //prevent unwanted re-setting of DefaultFormatsettings
  //Application.UpdateFormatSettings := False;
  //Now it is safe to set this for the lifetime of this program
  //DefaultFormatSetttings.DecimalSeparator := '.';
  progname := extractfilename(paramstr(0));
  progmode := pmMol2PS;
  if (pos('MOL2PS',upcase(progname))>0) or (pos('MOL2EPS',upcase(progname))>0) then 
    begin
      progmode := pmMol2PS; 
      if (pos('MOL2PS',upcase(progname))>0) then opt_eps := false else opt_eps := true;
    end else
    begin
      if pos('MOL2SVG',upcase(progname))>0 then progmode := pmMol2SVG else
        begin
          writeln('THOU SHALLST NOT RENAME ME!');
          halt(9);
        end;
    end;
  if (paramcount = 0) then
    begin
      show_usage;
      halt(1);
    end;
  init_globals;
  outbuffer := tstringlist.create;  // v0.4
  parse_args;
  if ringsearch_mode = rs_sar then max_vringsize := max_ringsize else
                                   max_vringsize := 10;
  left_trim(molfilename);
  if ((molfilename = '') and (not opt_stdin)) then
    begin
      show_usage;
      halt(2);
    end;
  if ((not fileexists(molfilename)) and (not opt_stdin)) then
    begin
      if ((length(molfilename) > 1) and (molfilename[1] = '-')) then
        begin
          show_usage;
        end else writeln('file ',molfilename,' not found!');
      halt(2);
    end;
  if opt_color then
    begin
      if (fileexists(rgbfilename)) then loadrgbtable(rgbfilename) 
        else opt_color := false;
    end;
  mol_count := 0;
  rxn_count := 0;
  li := 1;

  if (rxn_mode = false) then
    begin
      repeat
        begin
          ringsearch_mode := opt_rs;
          if ringsearch_mode = rs_sar then max_vringsize := max_ringsize else
                                           max_vringsize := 10;
          readinputfile(molfilename);
          li := 1;
          filetype := get_filetype(molfilename);
          if (filetype <> 'unknown') then
            begin
              mol_OK := true;
              if filetype = 'alchemy' then read_molfile(molfilename);
              if filetype = 'sybyl'   then read_mol2file(molfilename);
              if filetype = 'mdl'     then read_MDLmolfile(molfilename);
              inc(mol_count);
              count_neighbors;
              if (not mol_OK) or (n_atoms < 1) then 
                if (progmode = pmMol2PS) then 
                  writeout('%% %d:no valid structure found',[mol_count]) 
                else
                  writeout('<!-- %d:no valid structure found -->',[mol_count]) 
              else
                process_mol;
            end 
          else
            if (progmode = pmMol2PS) then 
              writeout('%% %d:unknown file format',[mol_count])
            else
              writeout('<!-- %d:unknown file format -->',[mol_count]);
          outbuffer.clear;
        end;
      until (mol_in_queue = false);
    end else    // reaction mode starts here
      begin
        open_rfile(molfilename);
        filetype := 'mdl';
        opt_showmolname := false;
        while not eof(rfile) do
          begin
            n_reactants := 0;
            n_products  := 0;
            if read_rxnheader = true then
              begin
                inc(rxn_count);
                x_shift := 0;
                xoffset := 1.5;  // may require some adjustment
                yoffset := 4.0; // may require some adjustment
                maxY := 2 * yoffset;
                if (n_reactants > 0) then
                  begin
                    for i := 1 to n_reactants do
                      begin
                        zap_molecule;
                        read_rxnmol;
                        inc(mol_count);
                        count_neighbors;
                        ringsearch_mode := opt_rs;
                        if ringsearch_mode = rs_sar then max_vringsize := max_ringsize else
                                                         max_vringsize := 10;
                        if opt_autoscale then scale_mol;
                        center_mol;
                        get_xminmax(x_min,x_max);
                        if (i > 0) then shift_x((x_shift - x_min + x_padding));  // v0.4b (was > 1)
                        get_xminmax(x_dummy,x_shift);
                        if (progmode = pmMol2PS) then 
                          begin
                            (*
                            writeln;
                            writeln('% reaction ',rxn_count,' reactant ',i);
                            *)
                            writeouts('');
                            writeout('%% reaction %d reactant %d',[rxn_count,i]);
                          end
                        else
                          writeout('<!-- reaction %d reactant %d -->',[rxn_count,i]);
                        process_mol;
                      end;
                  end;
                if (progmode = pmMol2PS) then 
                  begin
                    writeout('%% reaction %d arrow',[rxn_count]);
                    printPSarrow((x_shift + x_padding),0,(x_shift + x_padding + arrow_length),0)  
                  end
                else
                  begin
                    writeout('<!-- reaction %d arrow -->',[rxn_count]);
                    printSVGarrow((x_shift + x_padding),0,(x_shift + x_padding + arrow_length),0)
                  end;
                x_shift := x_shift + x_padding + arrow_length;
                if (n_products > 0) then
                  begin
                    for i := 1 to n_products do
                      begin
                        zap_molecule;
                        read_rxnmol;
                        inc(mol_count);
                        count_neighbors;
                        ringsearch_mode := opt_rs;
                        if ringsearch_mode = rs_sar then max_vringsize := max_ringsize else
                                                         max_vringsize := 10;
                        if opt_autoscale then scale_mol;
                        center_mol;
                        get_xminmax(x_min,x_max);
                        shift_x((x_shift - x_min + x_padding));
                        get_xminmax(x_dummy,x_shift);
                        if (progmode = pmMol2PS) then 
                          begin
                            (*
                            writeln;
                            writeln('% reaction ',rxn_count,' product ',i);
                            *)
                            writeouts('');
                            writeout('%% reaction %d product %d',[rxn_count,i]);
                          end
                        else
                          writeout('<!-- reaction %d product %d -->',[rxn_count,i]);
                        process_mol;
                      end;
                  end;
                if (progmode = pmMol2PS) then 
                  begin
                    write_PS_init;
                    writeln(outbuffer.text);
                    outbuffer.clear;
                    if not opt_eps then writeln('showpage');
                    writeln('% ----------------------end of image------------------------');
                  end;
                if (progmode = pmMol2SVG) then
                  begin
                    writeouts('</g>');
                    writeouts('</svg>');
                    // here comes the SVG header with the corrected dimensions
                    write_SVG_init;
                    // here comes the content of the output buffer
                    writeln(outbuffer.text);
                    outbuffer.clear;
                    // here comes the list of original  dimensions (for post-processing)
                    write_SVG_dimensions;
                  end;
                {$IFDEF debug}
                debugoutput('number of brackets: '+inttostr(n_brackets)+' number of Sgroups: '+inttostr(n_sgroups));
                {$ENDIF}
                skip_data;
                outbuffer.clear;  // v0.4
              end;  // if rxn_header = true
          end;  // while ...
      end;
  if rfile_is_open then close(rfile);
end.
