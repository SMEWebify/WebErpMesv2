Dim ExportQuoteFunctionFile As String = "QuoteToWEM.vb"
Dim ExportProductFunctionFile As String = "ProductToWEM.vb"

Dim ActionQuoteExport As String = "Quote to WEM"
Dim ActionProductExport As String = "Product to WEM"
Dim ActionToDo As StringAnswerFromList = RadWin.Ask2Buttons("Action to perform", "Choose the action to perform", ActionQuoteExport, ActionProductExport)

If ActionToDo.IsAvailable Then
    Dim FunctionFile As String
    If ActionToDo.Answer = ActionQuoteExport Then
        FunctionFile = ExportQuoteFunctionFile
    Else
        FunctionFile = ExportProductFunctionFile
    End If
    Dim strings() As String = {"QUOTE As Radquote.Business.Quotes.Quote"}
    Dim objects() As Object = {Quote}
    Radquote.Utilities.VbNetCode.CallExternalCode(FunctionFile, strings, objects, Quote, Nothing)
End If