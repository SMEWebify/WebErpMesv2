Imports System.Data.OleDb
Imports System.Data.Odbc
Imports System.Collections.Generic
Imports RadQuote.Business.Customers
Imports RadQuote.Business.Customers.CustomerSite
Imports RadQuote.Business.Customers.CustomerContact
Imports RadQuote.Configuration

Module ImportCustomersRadQuote
    Sub Main()
        Dim logContent As New List(Of String)
        logContent.Add("-----Import Customers-----")
        logContent.Add("Started at : " & DateTime.Now.ToString("dd/MM/yyyy HH:mm"))

        Try
            ' Connexion à la base MySQL de Laravel
            Dim connStr As String = "Driver={MySQL ODBC 8.4 ANSI Driver};DATABASE=erp;SERVER=localhost;PORT=3306;UID=root;PASSWORD=;"
            Using MyCon As New OdbcConnection(connStr)
                MyCon.Open()

                ' Lecture des clients
                Dim customerQuery As String = "SELECT * FROM companies"
                Using customerCmd As New OdbcCommand(customerQuery, MyCon)
                    Using customerReader As OdbcDataReader = customerCmd.ExecuteReader()
                        While customerReader.Read()
                            Dim currentCustomer As Customer = Nothing
                            Dim customerName As String = customerReader("label").ToString().Trim()
                            
                            ' Vérifier si le client existe dans RadQuote
                            For Each radquoteCustomer As KeyValuePair(Of String, String) In CompanyDefinitions.Current.IdsAndNames()
                                If radquoteCustomer.Value = customerName Then
                                    currentCustomer = CompanyDefinitions.Current.Get(radquoteCustomer.Key)
                                    Exit For
                                End If
                            Next
                            
                            ' Créer le client s'il n'existe pas
                            If currentCustomer Is Nothing Then
                                currentCustomer = CompanyDefinitions.Current.Add(customerName)
                            End If
                            
                            ' Mise à jour des informations du client
                            currentCustomer.WebSite = customerReader("website").ToString().Trim()
                            currentCustomer.MarginOrMarkup = Convert.ToDecimal(customerReader("discount").ToString().Trim())

                            ' Ajout du site principal (adresse principale)
                            Dim mainSite As CustomerSite = currentCustomer.FindSiteByName(customerReader("label").ToString().Trim())
                            If mainSite Is Nothing Then
                                mainSite = currentCustomer.AddSite()
                                mainSite.Name = If(Not String.IsNullOrWhiteSpace(customerReader("label").ToString().Trim()), customerReader("label").ToString().Trim(), "Site #1")
                            End If
                            mainSite.Address = customerReader("latitude").ToString().Trim() & " " & customerReader("longitude").ToString().Trim()
                            
                            ' Récupération des contacts associés
                            Dim contactQuery As String = "SELECT * FROM companies_contacts WHERE companies_id = '" & customerReader("id").ToString().Trim() & "'"
                            Using contactCmd As New OdbcCommand(contactQuery, MyCon)
                                Using contactReader As OdbcDataReader = contactCmd.ExecuteReader()
                                    While contactReader.Read()
                                        Dim currentContact As CustomerContact = mainSite.FindContactByFullName(contactReader("civility").ToString().Trim(), contactReader("last_name").ToString().Trim(), contactReader("first_name").ToString().Trim())
                                        If currentContact Is Nothing Then
                                            currentContact = mainSite.AddContact()
                                        End If
                                        currentContact.Surname = contactReader("last_name").ToString().Trim()
                                        currentContact.Forename = contactReader("first_name").ToString().Trim()
                                        currentContact.Phone = contactReader("phone").ToString().Trim()
                                        currentContact.Email = contactReader("email").ToString().Trim()
                                    End While
                                End Using
                            End Using

                            ' Sauvegarde du client
                            CompanyDefinitions.Current.Save()
                        End While
                    End Using
                End Using
                MyCon.Close()
            End Using
        Catch ex As Exception
            logContent.Add("***Errors***")
            logContent.Add(ex.Message)
        End Try

        logContent.Add("Ended at : " & DateTime.Now.ToString("dd/MM/yyyy HH:mm"))
        Console.WriteLine(String.Join(Environment.NewLine, logContent))
    End Sub
End Module
