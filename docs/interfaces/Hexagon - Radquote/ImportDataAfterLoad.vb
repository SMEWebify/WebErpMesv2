'''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''
''' Import clients WEM (MySQL) -> RadQuote
''' Compatible API RadQuote: CompanyDefinitions.Current.IdsAndNames / Get / Add / Save
''' Fix ODBC 8.4: no prepared parameters + no nested readers
'''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''

'--- Connexion (tu peux aussi les mettre en QuoteConstants)
Dim ServerIp As String = "localhost"
Dim ServerPort As String = "3306"
Dim DatabaseName As String = "wem"
Dim DatabaseUser As String = "root"
Dim DatabasePassword As String = ""

Dim ConnStr As String =
    "Driver={MySQL ODBC 8.4 ANSI Driver};" &
    "DATABASE=" & DatabaseName & ";" &
    "SERVER=" & ServerIp & ";" &
    "PORT=" & ServerPort & ";" &
    "UID=" & DatabaseUser & ";" &
    "PASSWORD=" & DatabasePassword & ";"

'--- Logs & stats
Dim logs As New System.Collections.Generic.List(Of String)
logs.Add("----- Import Customers WEM -> RadQuote -----")
logs.Add("Started at : " & Now().ToString("dd/MM/yyyy HH:mm"))

Dim imported As Integer = 0
Dim created As Integer = 0
Dim updated As Integer = 0
Dim skipped As Integer = 0
Dim errors As Integer = 0

'--- Helpers
Dim SafeStr As Func(Of Object, String) =
    Function(o As Object) As String
        If o Is Nothing OrElse o Is DBNull.Value Then Return ""
        Return o.ToString().Trim()
    End Function

Dim ToDec As Func(Of String, Decimal) =
    Function(s As String) As Decimal
        Dim d As Decimal = 0D
        If String.IsNullOrWhiteSpace(s) Then Return 0D
        s = s.Replace(",", ".")
        Decimal.TryParse(s, Globalization.NumberStyles.Any, Globalization.CultureInfo.InvariantCulture, d)
        If d = 0D Then Decimal.TryParse(s.Replace(".", ","), d)
        Return d
    End Function

Dim ToInt As Func(Of String, Integer) =
    Function(s As String) As Integer
        Dim i As Integer = 0
        Integer.TryParse(s, i)
        Return i
    End Function

'--- Find customer id by name (IdsAndNames est public)
Dim FindCustomerIdByName As Func(Of String, String) =
    Function(companyName As String) As String
        If String.IsNullOrWhiteSpace(companyName) Then Return ""
        For Each kvp As System.Collections.Generic.KeyValuePair(Of String, String) In _
            RadQuote.Business.Customers.CompanyDefinitions.Current.IdsAndNames()

            If String.Equals(kvp.Value, companyName, StringComparison.OrdinalIgnoreCase) Then
                Return kvp.Key
            End If
        Next
        Return ""
    End Function

Try
    Dim MyCon As New System.Data.Odbc.OdbcConnection(ConnStr)
    MyCon.Open()

    '--- 0) On charge toutes les companies en mémoire (pour éviter nested readers)
    Dim companies As New System.Collections.Generic.List(Of System.Collections.Generic.Dictionary(Of String, String))

    Dim sqlCompanies As String =
        "SELECT id, code, label, website, discount, longitude, latitude " &
        "FROM companies " &
        "WHERE active = 1 OR active IS NULL"

    Dim cmdCompanies As New System.Data.Odbc.OdbcCommand(sqlCompanies, MyCon)
    Dim rdCompanies As System.Data.Odbc.OdbcDataReader = cmdCompanies.ExecuteReader()
    While rdCompanies.Read()
        Dim row As New System.Collections.Generic.Dictionary(Of String, String)
        row("id") = SafeStr.Invoke(rdCompanies("id"))
        row("code") = SafeStr.Invoke(rdCompanies("code"))
        row("label") = SafeStr.Invoke(rdCompanies("label"))
        row("website") = SafeStr.Invoke(rdCompanies("website"))
        row("discount") = SafeStr.Invoke(rdCompanies("discount"))
        row("longitude") = SafeStr.Invoke(rdCompanies("longitude"))
        row("latitude") = SafeStr.Invoke(rdCompanies("latitude"))
        companies.Add(row)
    End While
    rdCompanies.Close()

    '--- 1) Boucle import
    For Each cRow As System.Collections.Generic.Dictionary(Of String, String) In companies
        imported += 1

        Dim wemCompanyIdStr As String = cRow("id")
        Dim wemCompanyId As Integer = ToInt.Invoke(wemCompanyIdStr)
        Dim wemCode As String = cRow("code")
        Dim name As String = cRow("label")
        Dim website As String = cRow("website")
        Dim discountStr As String = cRow("discount")
        Dim lng As String = cRow("longitude")
        Dim lat As String = cRow("latitude")

        If String.IsNullOrWhiteSpace(name) Then
            skipped += 1
            logs.Add("SKIP companies.id=" & wemCompanyIdStr & " (label vide)")
            Continue For
        End If

        Try
            '--- Get or Create
            Dim customer As Object = Nothing
            Dim customerId As String = FindCustomerIdByName.Invoke(name)

            If Not String.IsNullOrWhiteSpace(customerId) Then
                customer = RadQuote.Business.Customers.CompanyDefinitions.Current.Get(customerId)
                updated += 1
                logs.Add("UPDATE: " & name)
            Else
                customer = RadQuote.Business.Customers.CompanyDefinitions.Current.Add(name) ' public
                created += 1
                logs.Add("CREATE: " & name)
            End If

            '--- Mapping client
            Try : customer.WebSite = website : Catch : End Try
            Try : customer.MarginOrMarkup = ToDec.Invoke(discountStr) : Catch : End Try

            '--- adress (companies_addresses) : pas de paramètres ODBC
            Dim addrLabel As String = ""
            Dim addradress As String = ""
            Dim addrZip As String = ""
            Dim addrCity As String = ""
            Dim addrCountry As String = ""

            Dim sqlAddr As String =
                "SELECT label, adress, zipcode, city, country, `default`, ordre " &
                "FROM companies_addresses " &
                "WHERE companies_id = " & wemCompanyId & " " &
                "ORDER BY `default` DESC, ordre ASC, id ASC " &
                "LIMIT 1"

            Dim cmdAddr As New System.Data.Odbc.OdbcCommand(sqlAddr, MyCon)
            Dim rdAddr As System.Data.Odbc.OdbcDataReader = cmdAddr.ExecuteReader()
            If rdAddr.Read() Then
                addrLabel = SafeStr.Invoke(rdAddr("label"))
                addradress = SafeStr.Invoke(rdAddr("adress"))
                addrZip = SafeStr.Invoke(rdAddr("zipcode"))
                addrCity = SafeStr.Invoke(rdAddr("city"))
                addrCountry = SafeStr.Invoke(rdAddr("country"))
            End If
            rdAddr.Close()

            '--- Site
            Dim siteName As String = name
            If Not String.IsNullOrWhiteSpace(addrLabel) Then siteName = addrLabel

            Dim site As Object = Nothing
            Try : site = customer.FindSiteByName(siteName) : Catch : site = Nothing : End Try

            If site Is Nothing Then
                site = customer.AddSite()
                Try : site.Name = siteName : Catch : End Try
            End If

            If site IsNot Nothing Then
                If Not String.IsNullOrWhiteSpace(wemCode) Then
                    Try : site.ExternalId = wemCode : Catch : End Try
                End If

                If Not String.IsNullOrWhiteSpace(addradress) Then
                    Try : site.adress = addradress : Catch : End Try
                End If
                If Not String.IsNullOrWhiteSpace(addrZip) Then
                    Try : site.Postcode = addrZip : Catch : End Try
                End If
                If Not String.IsNullOrWhiteSpace(addrCity) Then
                    Try : site.City = addrCity : Catch : End Try
                End If
                If Not String.IsNullOrWhiteSpace(addrCountry) Then
                    Try : site.Country = addrCountry : Catch : End Try
                End If

                Dim gps As String = (lat & " " & lng).Trim()
                If Not String.IsNullOrWhiteSpace(gps) Then
                    Try
                        If String.IsNullOrWhiteSpace(site.adress) Then
                            site.adress = gps
                        ElseIf Not site.adress.ToString().Contains("GPS:") Then
                            site.adress = site.adress.ToString() & " | GPS: " & gps
                        End If
                    Catch
                    End Try
                End If
            End If

            '--- Contacts (companies_contacts) : pas de paramètres ODBC
            Dim sqlContacts As String =
                "SELECT civility, first_name, name, number, mobile, mail, `default`, ordre " &
                "FROM companies_contacts " &
                "WHERE companies_id = " & wemCompanyId & " " &
                "ORDER BY `default` DESC, ordre ASC, id ASC"

            Dim cmdContacts As New System.Data.Odbc.OdbcCommand(sqlContacts, MyCon)
            Dim rdContacts As System.Data.Odbc.OdbcDataReader = cmdContacts.ExecuteReader()

            While rdContacts.Read()
                If site Is Nothing Then Exit While

                Dim civ As String = SafeStr.Invoke(rdContacts("civility"))
                Dim fn As String = SafeStr.Invoke(rdContacts("first_name"))
                Dim ln As String = SafeStr.Invoke(rdContacts("name"))
                Dim phone1 As String = SafeStr.Invoke(rdContacts("mobile"))
                Dim phone2 As String = SafeStr.Invoke(rdContacts("number"))
                Dim email As String = SafeStr.Invoke(rdContacts("mail"))

                If String.IsNullOrWhiteSpace(ln) AndAlso String.IsNullOrWhiteSpace(fn) Then
                    Continue While
                End If

                Dim contact As Object = Nothing
                Try : contact = site.FindContactByFullName(civ, ln, fn) : Catch : contact = Nothing : End Try
                If contact Is Nothing Then
                    Try : contact = site.AddContact() : Catch : contact = Nothing : End Try
                End If

                If contact IsNot Nothing Then
                    Try : contact.Surname = ln : Catch : End Try
                    Try : contact.Forename = fn : Catch : End Try
                    Try : contact.Phone = If(Not String.IsNullOrWhiteSpace(phone1), phone1, phone2) : Catch : End Try
                    Try : contact.Email = email : Catch : End Try
                End If
            End While
            rdContacts.Close()

            '--- Save
            RadQuote.Business.Customers.CompanyDefinitions.Current.Save()

        Catch exPerCustomer As Exception
            errors += 1
            logs.Add("ERROR companies.id=" & wemCompanyIdStr & " / " & name & " -> " & exPerCustomer.Message)
        End Try
    Next

    MyCon.Close()

Catch ex As Exception
    errors += 1
    logs.Add("*** ERROR (GLOBAL) ***")
    logs.Add(ex.Message)
    logs.Add(ex.StackTrace)
Finally
    logs.Add("Ended at : " & Now().ToString("dd/MM/yyyy HH:mm"))
    logs.Add("Stats: imported=" & imported & " created=" & created & " updated=" & updated & " skipped=" & skipped & " errors=" & errors)
    RadWin.ShowMsgBox("Import clients WEM -> RadQuote", String.Join(vbCrLf, logs))
End Try
