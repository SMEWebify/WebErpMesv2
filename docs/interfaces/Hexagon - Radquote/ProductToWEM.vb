
'Variables from RADQuote Constants
Dim ServerIp As String = RadQuote.Business.QuoteConstants.QuoteConstants.Current.GetQuoteConstant("qc_wem_Serveur_IP","localhost").Value.StringValue
Dim ServerPort As String = RadQuote.Business.QuoteConstants.QuoteConstants.Current.GetQuoteConstant("qc_wem_Serveur_port","3306").Value.StringValue
Dim DatabaseName As String = RadQuote.Business.QuoteConstants.QuoteConstants.Current.GetQuoteConstant("qc_wem_Nom_base","erp").Value.StringValue
Dim DatabaseUser As String = RadQuote.Business.QuoteConstants.QuoteConstants.Current.GetQuoteConstant("qc_wem_Utilisateur","").Value.StringValue
Dim DatabasePassword As String = RadQuote.Business.QuoteConstants.QuoteConstants.Current.GetQuoteConstant("qc_wem_Mot_de_passe","").Value.StringValue
Dim MethodsUnitCode As String = RadQuote.Business.QuoteConstants.QuoteConstants.Current.GetQuoteConstant("qc_wem_Methods_Unit_Code","").Value.StringValue
Dim MethodsFamilyCode As String = RadQuote.Business.QuoteConstants.QuoteConstants.Current.GetQuoteConstant("qc_wem_Methods_Family_Code","").Value.StringValue
Dim MethodsServiceCode As String = RadQuote.Business.QuoteConstants.QuoteConstants.Current.GetQuoteConstant("qc_wem_Methods_Service_Code","").Value.StringValue

'Database table names
Const ProductTable As String = "products"
Const TasksTable As String = "tasks"
Const SubAssembliesTable As String = "sub_assemblies"
Const UserTable As String = "users"
Const MethodsServicesTable As String = "methods_services"
Const MethodsUnitsTable As String = "methods_units"
Const MethodsFamiliesTable As String = "methods_families"

Const ProductCode As String = "code"
Const ProductLabel As String = "label"
Const ProductInd As String = "ind"
Const ProductServiceId As String = "methods_services_id"
Const ProductFamilieId As String = "methods_families_id"
Const ProductPurchased As String = "purchased"
Const ProductPurchasedPrice As String = "purchased_price"
Const ProductSold As String = "sold"
Const ProductSellingPrice As String = "selling_price"
Const ProductUnitId As String = "methods_units_id"
Const ProductMaterial As String = "material"
Const ProductThickness As String = "thickness"
Const ProductWeight As String = "weight"
Const ProductXsize As String = "x_size"
Const ProductYsize As String = "y_size"
Const ProductZsize As String = "z_size"
Const ProductXoversizesize As String = "x_oversize"
Const ProductYoversizesize As String = "y_oversize"
Const ProductZoversizesize As String = "z_oversize"
Const ProductComment As String = "comment"
Const ProductDiameter As String = "diameter"
Const CreatedAt As String = "created_at"
Const UpdatedAt As String = "updated_at"

Const TaskLabel As String = "label"
Const TaskOrdre As String = "ordre"
Const TaskProductId As String = "products_id"
Const TaskServiceId As String = "methods_services_id"
Const TaskSettingTime As String = "seting_time"
Const TaskUnitTime As String = "unit_time"
Const TaskStatuId As String = "status_id"
Const TaskType As String = "type"
Const TaskQty As String = "qty"
Const TaskUnitCost As String = "unit_cost"
Const TaskUnitPrice As String = "unit_price"
Const TaskMethodsUnitsId As String = "methods_units_id"

Dim messages As New Dictionary(Of Integer, String)
Dim MyCon As New Odbc.OdbcConnection
'MyCon.ConnectionString = "Driver={PostgreSQL ANSI};database=" & DatabaseName & ";server=" & ServerIp & ";port=" & ServerPort & ";uid=" & DatabaseUser & ";pwd=" & DatabasePassword & ";"
MyCon.ConnectionString = "Driver={MySQL ODBC 8.4 ANSI Driver};DATABASE=" & DatabaseName & ";SERVER=" & ServerIp & ";PORT=" & ServerPort & ";UID=" & DatabaseUser & ";PASSWORD=" & DatabasePassword & ";"

Dim ToSqlString As Func(of String, String) = _
	Function(s As String) As String
		If Not String.IsNullOrWhiteSpace(s) Then
			s = s.Replace("'","''")
		End If
		Return s
	End Function



'FUNCTION FOR ADD OPERTATION TO QUOTE LINE
Dim CreatePartOperation As Action(Of RadQuote.Business.Operations.Overview.OperationResultsOnPart, integer, integer) =
    Sub(op As RadQuote.Business.Operations.Overview.OperationResultsOnPart, ProductId As integer, MethodsUnitId As integer)
		Dim operationId As Integer
		Dim operationType As Integer
		Dim operationGetterQueryString As String = "SELECT * FROM " & MethodsServicesTable
    	Dim operationGetterCommand As New Odbc.OdbcCommand(operationGetterQueryString, MyCon)
    	Dim operationGetterReader As Odbc.OdbcDataReader = operationGetterCommand.ExecuteReader()

    	While operationGetterReader.Read()
			If operationGetterReader("code").ToString().Trim() = op.OperationDefinition.ExternalId Then
        		operationId = operationGetterReader("id")
        		operationType = operationGetterReader("type")
			End If
    	End While
    	operationGetterReader.Close()

		'Type service
		' 1 = Productive
		' 2 = Raw material
		' 3 = Raw material (Sheet)
		' 4 = Raw material (Profil)
		' 5 = Raw material (block)
		' 6 = Supplies
		' 7 = Sub-contracting
		' 8 = Composed component
		If operationId <> 0 AND operationType = 1 Then
			Dim operationQueryString As String = "INSERT INTO " & TasksTable & "(" & _
																					TaskLabel & "," & _
																					TaskOrdre & "," & _
																					TaskProductId & "," & _
																					TaskServiceId & "," & _
																					TaskStatuId & "," & _
																					TaskType & "," & _
																					TaskQty & "," & _
																					TaskSettingTime & "," & _
																					TaskUnitTime & "," & _
																					TaskUnitCost & "," & _
																					TaskUnitPrice & "," & _
																					TaskMethodsUnitsId & ")" & _
																					" VALUES ('" & _
																					op.OperationDefinition.Name & "','" & _
																					op.OperationDefinition.Index & "','" & _
																					ProductId & "','" & _
																					operationId & "','1','" & _
																					operationType & "','1','" & _
																					Math.Round(op.FullOtherTotalTime/100,2).ToString().Replace(",",".") & "','" & _
																					Math.Round(op.FullUnitProductTime/100,2).ToString().Replace(",",".") & "','" & _
																					Math.Round(op.UnitCost,2).ToString().Replace(",",".") & "','" & _
																					Math.Round(op.UnitPrice,2).ToString().Replace(",",".") & "','" & _
																					MethodsUnitId & "')"
																					
																					
    		Dim operationCommand As New Odbc.OdbcCommand(operationQueryString, MyCon)
			operationCommand.ExecuteNonQuery()
		End If
	End Sub

'FUNCTION OR CREATE QUOTE LINE
Dim CreateQuotePart As Action(Of RadQuote.Business.Parts.PartLine, integer, integer, integer) =
    Sub(p As RadQuote.Business.Parts.PartLine, MethodsServiceId As integer , MethodsUnitId As integer, MethodsFamilyId As integer)
		Dim ProductId As Integer
		Dim thickness As Decimal = 0
		Dim Material As string = ""
		
		If TypeOf(p.Part) Is RadQuote.Business.Parts.SymbolPart Then
			thickness = RadQuote.Business.Materials.Materials.Current.GetMaterial(CType(p.Part, RadQuote.Business.Parts.SymbolPart).MaterialId).Thicknesses.GetTechnology(CType(p.Part, RadQuote.Business.Parts.SymbolPart).ThicknessId).Description
			Material = ""
		Else
			thickness = 0
			Material = ""
		End If
		
		Dim partQueryString As String = "INSERT INTO " & ProductTable & "(" & _
																				ProductCode & "," & _
																				ProductLabel & "," & _
																				ProductInd & "," & _
																				ProductServiceId & "," & _
																				ProductFamilieId & "," & _
																				ProductPurchased & "," & _
																				ProductPurchasedPrice & "," & _
																				ProductSold & "," & _
																				ProductSellingPrice & "," & _
																				ProductUnitId & "," & _
																				ProductMaterial & "," & _
																				ProductThickness & "," & _
																				ProductWeight & "," & _
																				ProductXsize & "," & _
																				ProductYsize & "," & _
																				ProductZsize & "," & _
																				ProductComment & "," & _
																				CreatedAt  & "," & _
																				UpdatedAt &")" & _
																				" VALUES ('" & _
																				p.Part.Names(0).ToString() & "','" & _
																				p.Part.Names(1).ToString() & "','" & _
																				p.Part.Names(2).ToString() & "','" & _
																				MethodsServiceId & "','" & _
																				MethodsFamilyId & "','1','" & _
																				p.Part.SoldUnitPrice.ToString().Replace(",",".") & "','1','" & _
																				p.Part.SoldUnitPrice.ToString().Replace(",",".") & "','" & _
																				MethodsUnitId & "','" & _ 
																				Material & "','" & _
																				thickness.ToString().Replace(",",".") & "','" & _
																				p.Part.Weight.ToString().Replace(",",".") & "','" & _
																				p.Part.X.ToString().Replace(",",".") & "','" & _
																				p.Part.Y.ToString().Replace(",",".") & "','" & _
																				p.Part.Z.ToString().Replace(",",".") & "','" & _
																				ToSqlString(p.Part.Comment) & "','" & _
																				Now().ToString("yyyy-MM-dd HH:mm:ss") & "','" & _
																				Now().ToString("yyyy-MM-dd HH:mm:ss") & "')"
    	Dim partCommand As New Odbc.OdbcCommand(partQueryString, MyCon)
		partCommand.ExecuteNonQuery()
        
		'Get last ID
		Dim scopeIdentityQuery As String = "SELECT LAST_INSERT_ID()"
		Dim identityCommand As New Odbc.OdbcCommand(scopeIdentityQuery, MyCon)
		ProductId  = Convert.ToInt32(identityCommand.ExecuteScalar())
		
		messages.Add(ProductId ,"Traitement de l'élément " & p.Part.Names(0).ToString() & " réussi.")
																				
		For Each op As RadQuote.Business.Operations.Result.OperationResult In p.Part.OperationCalculations
            If op.IsUsedInCalculations _
                AndAlso TypeOf (op.OperationDefinition) Is RadQuote.Business.Operations.PartOperation Then			
				Dim OperationResults As RadQuote.Business.Operations.Overview.OperationResultsOnPart =
            		p.OperationsResults.FirstOrDefault(Function(op2) op2.OperationDefinition.Name = op.OperationDefinition.Name)
				CreatePartOperation(OperationResults, ProductId, MethodsUnitId)
			End If
		Next
		
		For Each sp As RadQuote.Business.Parts.PartLine In p.SubParts
			CreateQuotePart(sp, MethodsServiceId, MethodsUnitId, MethodsFamilyId)
        Next
	End Sub

MyCon.Open()
Dim ContinueProcess As Boolean = True
If MyCon.State = ConnectionState.Open Then

	Dim MethodsServiceId As Integer
	Dim MethodsUnitId As Integer
	Dim MethodsFamilyId As Integer
	
	'READ METHODS UNIT INFORMATION FROM WEM TABLE
	Dim MethodsUnitQueryString As String = "SELECT * FROM " & MethodsUnitsTable & " WHERE " & "code" & "='" & MethodsUnitCode & "'" 
    Dim MethodsUnitCommand As New Odbc.OdbcCommand(MethodsUnitQueryString, MyCon)
    Dim MethodsUnitReader As Odbc.OdbcDataReader = MethodsUnitCommand.ExecuteReader()
    While MethodsUnitReader.Read()
		MethodsUnitId = MethodsUnitReader("id")
    End While
    MethodsUnitReader.Close()

	'READ METHODS FAMILY INFORMATION FROM WEM TABLE
	Dim MethodsFamilyQueryString As String = "SELECT * FROM " & MethodsFamiliesTable & " WHERE " & "code" & "='" & MethodsFamilyCode & "'" 
    Dim MethodsFamilyCommand As New Odbc.OdbcCommand(MethodsFamilyQueryString, MyCon)
    Dim MethodsFamilyReader As Odbc.OdbcDataReader = MethodsFamilyCommand.ExecuteReader()
    While MethodsFamilyReader.Read()
		MethodsFamilyId = MethodsFamilyReader("id")
    End While
    MethodsFamilyReader.Close()
	
	'READ METHODS SERVICE INFORMATION FROM WEM TABLE
	Dim MethodsServiceQueryString As String = "SELECT * FROM " & MethodsServicesTable & " WHERE " & "code" & "='" & MethodsServiceCode & "'" 
    Dim MethodsServiceCommand As New Odbc.OdbcCommand(MethodsServiceQueryString, MyCon)
    Dim MethodsServiceReader As Odbc.OdbcDataReader = MethodsServiceCommand.ExecuteReader()
    While MethodsServiceReader.Read()
		MethodsServiceId = MethodsServiceReader("id")
    End While
    MethodsServiceReader.Close()
	
	For Each p As RadQuote.Business.Parts.PartLine In QUOTE.Parts.SubParts				
		CreateQuotePart(p, MethodsServiceId, MethodsUnitId, MethodsFamilyId)
	Next
	
End If
MyCon.Close()

If ContinueProcess Then
	Dim resultMessage As String = ""
	For Each kvp As KeyValuePair(Of Integer, String) In messages
		resultMessage &= kvp.Value & vbCrLf
	Next

	RadWin.ShowMsgBox("Export", "Résultats des traitements :" & vbCrLf & resultMessage)
End If  