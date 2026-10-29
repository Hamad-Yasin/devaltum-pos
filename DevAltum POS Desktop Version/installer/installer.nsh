!macro customHeader
  !define MUI_FINISHPAGE_TEXT "DevAltum POS has been installed on your computer.$\r$\n$\r$\n==============================$\r$\nDEFAULT STORE LOGIN:$\r$\nEmail: store@devaltum.pos$\r$\nPassword: Store@123$\r$\n==============================$\r$\n$\r$\nThank you for choosing DevAltum POS! Click Finish to start."
!macroend

!macro customInstall
  MessageBox MB_OK|MB_ICONINFORMATION "🎉 Thank you for choosing DevAltum POS!$\r$\n$\r$\n==============================$\r$\nDEFAULT STORE LOGIN CREDENTIALS:$\r$\nEmail: store@devaltum.pos$\r$\nPassword: Store@123$\r$\n==============================$\r$\n$\r$\nClick OK to start your DevAltum POS system."
!macroend
