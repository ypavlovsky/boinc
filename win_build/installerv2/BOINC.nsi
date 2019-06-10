; This file is part of BOINC.
; http://boinc.berkeley.edu
; Copyright (C) 2019 University of California
;
; BOINC is free software; you can redistribute it and/or modify it
; under the terms of the GNU Lesser General Public License
; as published by the Free Software Foundation,
; either version 3 of the License, or (at your option) any later version.
;
; BOINC is distributed in the hope that it will be useful,
; but WITHOUT ANY WARRANTY; without even the implied warranty of
; MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
; See the GNU Lesser General Public License for more details.
;
; You should have received a copy of the GNU Lesser General Public License
; along with BOINC.  If not, see <http://www.gnu.org/licenses/>.

SetCompressor /SOLID lzma

!include "MUI2.nsh"

!include "include\boinc_configuration_page.nsh"

!define product_name "BOINC"
!define product_version "7.15.0"
!define product_arch "intelx86"
!define license_file "redist\0409\eula.rtf"
!define setup_icon "redist\setup.ico"
!define setup_splash "redist\setup.bmp"
!define folder_icon "redist\folder.ico"
;var arch "x86_64"
!define out_file "boinc_${product_version}_${product_arch}.exe"
!if ${product_arch} == "intelx86"
    !define arch_path "Win32"
!else
    !define arch_path "x64"
!endif
!define boinc_release_path "..\Build\${arch_path}\Release"

!define MUI_ABORTWARNING

!define MUI_ICON "${setup_icon}"
!define MUI_UNICON "${setup_icon}"

!insertmacro MUI_PAGE_WELCOME
!insertmacro MUI_PAGE_LICENSE "${license_file}"
!insertmacro MUI_PAGE_COMPONENTS
!insertmacro MUI_PAGE_DIRECTORY
!insertmacro BOINC_PAGE_CONFIGURATION

!insertmacro MUI_LANGUAGE "English"

Name "${product_name}"
Caption "${product_name}"
OutFile "${out_file}"

XPStyle on

Function .onInit
    InitPluginsDir
    File /oname=$PLUGINSDIR\splash.bmp "${setup_splash}"
    advsplash::show 1000 600 400 -1 $PLUGINSDIR\splash
    Pop $0
    Delete $PLUGINSDIR\splash.bmp
FunctionEnd

Section "-Common"
    SetOutPath $INSTDIR
    File "..\..\curl\ca-bundle.crt"
    File "..\..\COPYING"
    File "..\..\COPYING.LESSER"
    File "..\..\COPYRIGHT"
SectionEnd

SectionGroup "BOINC Client"
    Section "Client"
        SetOutPath $INSTDIR
        File "${boinc_release_path}\boinc.exe"

        SetOutPath $boinc_configuration_page_data_dir
        File "redist\all_projects_list.xml"

        CreateDirectory "$boinc_configuration_page_data_dir\projects"

        CreateDirectory "$boinc_configuration_page_data_dir\slots"
    SectionEnd
    Section "Command line tool"
        SetOutPath $INSTDIR
        File "${boinc_release_path}\boinccmd.exe"
    SectionEnd
    Section "Service controller"
        SetOutPath $INSTDIR
    SectionEnd
    Section "Tray utility"
        SetOutPath $INSTDIR
    SectionEnd
SectionGroupEnd

Section "BOINC Manager"
    SetOutPath $INSTDIR
SectionEnd

Section "BOINC Screensaver"
SectionEnd
