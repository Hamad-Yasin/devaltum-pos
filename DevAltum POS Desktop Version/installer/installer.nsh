!include nsDialogs.nsh
!include LogicLib.nsh

!macro customHeader
  !define MUI_FINISHPAGE_TEXT "DevAltum POS has been installed on your computer.$\r$\n$\r$\n==============================$\r$\nDEFAULT STORE LOGIN:$\r$\nEmail: store@devaltum.pos$\r$\nPassword: Store@123$\r$\n==============================$\r$\n$\r$\nThank you for choosing DevAltum POS! Click Finish to start."
!macroend

!macro customInstall
  MessageBox MB_OK|MB_ICONINFORMATION "🎉 Thank you for choosing DevAltum POS!$\r$\n$\r$\n==============================$\r$\nDEFAULT STORE LOGIN CREDENTIALS:$\r$\nEmail: store@devaltum.pos$\r$\nPassword: Store@123$\r$\n==============================$\r$\n$\r$\nClick OK to start your DevAltum POS system."
!macroend

!ifdef BUILD_UNINSTALLER

Function un.ConfirmDataRemovalPage
  nsDialogs::Create 1018
  Pop $R1
  ${If} $R1 == error
    Abort
  ${EndIf}

  ${NSD_CreateLabel} 0 0 100% 36u "DevAltum POS is about to be uninstalled from your computer.$\r$\n$\r$\nBy default, all your store databases, sales records, customer dues, and inventory are kept 100% safe so you do not lose any data if you reinstall later."
  Pop $R0

  ${NSD_CreateCheckbox} 5u 46u 95% 18u "Delete all store databases, sales records, and business data (Permanent)"
  Pop $R2
  ${NSD_SetState} $R2 ${BST_UNCHECKED}

  nsDialogs::Show
FunctionEnd

Function un.ConfirmDataRemovalPageLeave
  ${NSD_GetState} $R2 $R0
  ${If} $R0 == ${BST_CHECKED}
    StrCpy $R9 "1"
  ${Else}
    StrCpy $R9 "0"
  ${EndIf}
FunctionEnd

!macro customUnWelcomePage
  !insertmacro MUI_UNPAGE_WELCOME
  UninstPage custom un.ConfirmDataRemovalPage un.ConfirmDataRemovalPageLeave
!macroend

!macro customUnInstall
  ; Silently terminate any lingering background database or PHP processes to guarantee complete file unlock
  nsExec::Exec 'taskkill /F /IM mysqld.exe /IM php-cgi.exe /IM php.exe'

  ${If} $R9 == "1"
    SetShellVarContext current
    RMDir /r "$APPDATA\devaltump-pos-desktop"
    RMDir /r "$LOCALAPPDATA\devaltump-pos-desktop"
    RMDir /r "$LOCALAPPDATA\devaltump-pos-desktop-updater"
    SetShellVarContext all
  ${EndIf}
!macroend

!endif
