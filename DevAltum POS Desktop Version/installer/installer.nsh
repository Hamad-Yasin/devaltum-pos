!include nsDialogs.nsh
!include LogicLib.nsh

!macro customHeader
  !define MUI_FINISHPAGE_TEXT "DevAltum POS has been installed on your computer.$\r$\n$\r$\n==============================$\r$\nDEFAULT STORE LOGIN:$\r$\nEmail: store@devaltum.pos$\r$\nPassword: Store@123$\r$\n==============================$\r$\n$\r$\nThank you for choosing DevAltum POS! Click Finish to start."
!macroend

!macro customInstall
  ; Register firewall rule for local MariaDB
  nsExec::Exec 'netsh advfirewall firewall add rule name="DevAltum POS Database Engine" dir=in action=allow program="$INSTDIR\resources\runtime\mariadb\bin\mysqld.exe" enable=yes'
  MessageBox MB_OK|MB_ICONINFORMATION "🎉 Thank you for choosing DevAltum POS!$\r$\n$\r$\n==============================$\r$\nDEFAULT STORE LOGIN CREDENTIALS:$\r$\nEmail: store@devaltum.pos$\r$\nPassword: Store@123$\r$\n==============================$\r$\n$\r$\nClick OK to start your DevAltum POS system."
!macroend

!ifdef BUILD_UNINSTALLER

Function un.ConfirmDataRemovalPage
  nsDialogs::Create 1018
  Pop $R1
  ${If} $R1 == error
    Abort
  ${EndIf}

  ${NSD_CreateLabel} 0 0 100% 36u "DevAltum POS is about to be uninstalled from your computer.$\r$\n$\r$\nCheck the box below if you want to completely remove all databases, invoices, and sales data for a fresh clean reinstallation."
  Pop $R0

  ${NSD_CreateCheckbox} 5u 46u 95% 18u "Delete all store databases, sales records, and business data (Complete Clean Wipe)"
  Pop $R2
  ${NSD_SetState} $R2 ${BST_CHECKED}

  nsDialogs::Show
FunctionEnd

Function un.ConfirmDataRemovalPageLeave
  ${NSD_GetState} $R2 $R0
  ${If} $R0 == ${BST_CHECKED}
    WriteRegStr HKCU "Software\DevAltumPOS" "UninstallRemoveData" "1"
  ${Else}
    WriteRegStr HKCU "Software\DevAltumPOS" "UninstallRemoveData" "0"
  ${EndIf}
FunctionEnd

!macro customUnWelcomePage
  !insertmacro MUI_UNPAGE_WELCOME
  UninstPage custom un.ConfirmDataRemovalPage un.ConfirmDataRemovalPageLeave
!macroend

!macro customUnInstall
  ; Silently terminate any lingering background database or PHP processes to guarantee complete file unlock
  nsExec::Exec 'taskkill /F /T /IM mysqld.exe /IM php-cgi.exe /IM php.exe'
  Sleep 2000

  ; Remove firewall rule
  nsExec::Exec 'netsh advfirewall firewall delete rule name="DevAltum POS Database Engine"'

  ; Read user preference from registry (safe against register clobbering)
  ReadRegStr $0 HKCU "Software\DevAltumPOS" "UninstallRemoveData"
  ${If} $0 == "1"
    SetShellVarContext current
    RMDir /r "$APPDATA\devaltump-pos-desktop"
    RMDir /r "$LOCALAPPDATA\devaltump-pos-desktop"
    RMDir /r "$LOCALAPPDATA\devaltump-pos-desktop-updater"
    RMDir /r "$APPDATA\DevAltum POS"
    
    ; Extra guarantee using cmd rd in case of attributes or delayed file lock release
    nsExec::Exec 'cmd.exe /c "rd /s /q \"%APPDATA%\devaltump-pos-desktop\""'
    nsExec::Exec 'cmd.exe /c "rd /s /q \"%LOCALAPPDATA%\devaltump-pos-desktop\""'
    nsExec::Exec 'cmd.exe /c "rd /s /q \"%LOCALAPPDATA%\devaltump-pos-desktop-updater\""'
    nsExec::Exec 'cmd.exe /c "rd /s /q \"%APPDATA%\DevAltum POS\""'
    SetShellVarContext all
  ${EndIf}

  DeleteRegKey HKCU "Software\DevAltumPOS"
!macroend

!endif
